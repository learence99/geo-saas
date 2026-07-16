<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeoCitation;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Support\GeoFlow\ApiKeyCrypto;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GeoCitationController extends Controller
{
    private const PYTHON_BIN = '/opt/geo-optimizer-venv/bin/geo';

    private const SETTING_KEY_PROVIDER = 'geo_citations_provider';

    private const SETTING_KEY_API_KEY = 'geo_citations_api_key';

    private const PROVIDER_ENV_KEYS = [
        'openai' => 'OPENAI_API_KEY',
        'anthropic' => 'ANTHROPIC_API_KEY',
        'groq' => 'GROQ_API_KEY',
        'perplexity' => 'PERPLEXITY_API_KEY',
    ];

    private const DEFAULT_PROVIDER = 'openai';

    public function __construct(private readonly ApiKeyCrypto $apiKeyCrypto) {}

    public function index(): View
    {
        $citations = GeoCitation::orderByDesc('created_at')->paginate(15);
        $latest = GeoCitation::where('status', 'completed')->orderByDesc('created_at')->first();
        $storedKey = $this->storedApiKey();

        return view('admin.geo-citations.index', [
            'pageTitle' => 'AI 品牌引用检测',
            'activeMenu' => 'geo_citations',
            'citations' => $citations,
            'latest' => $latest,
            'apiKeyMasked' => $storedKey === '' ? '' : $this->maskedStoredKey(),
            'hasApiKey' => $storedKey !== '',
            'currentProvider' => $this->storedProvider(),
            'providers' => array_keys(self::PROVIDER_ENV_KEYS),
            'defaultDomain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: '',
            'defaultBrand' => (string) \App\Support\AdminWeb::siteName(),
        ]);
    }

    public function show(int $id): View
    {
        $citation = GeoCitation::findOrFail($id);

        return view('admin.geo-citations.show', [
            'pageTitle' => 'AI 品牌引用检测详情',
            'activeMenu' => 'geo_citations',
            'citation' => $citation,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:'.implode(',', array_keys(self::PROVIDER_ENV_KEYS))],
            'api_key' => ['required', 'string', 'max:500'],
        ]);

        SiteSetting::updateOrCreate(
            ['setting_key' => self::SETTING_KEY_PROVIDER],
            ['setting_value' => $validated['provider']]
        );

        SiteSetting::updateOrCreate(
            ['setting_key' => self::SETTING_KEY_API_KEY],
            ['setting_value' => $this->apiKeyCrypto->encrypt($validated['api_key'])]
        );

        return redirect()
            ->route('admin.geo-citations.index')
            ->with('message', 'API Key 已保存');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:150'],
            'domain' => ['required', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:255'],
        ]);

        $apiKey = $this->storedApiKey();
        $provider = $this->storedProvider();
        if ($apiKey === '') {
            return redirect()
                ->route('admin.geo-citations.index')
                ->withErrors(['api_key' => '请先在下方配置 API Key 后再发起检测']);
        }

        $citation = GeoCitation::create([
            'brand' => $validated['brand'],
            'domain' => $validated['domain'],
            'topic' => $validated['topic'] ?? null,
            'status' => 'running',
            'triggered_by' => Auth::guard('admin')->id(),
        ]);

        try {
            $args = [
                self::PYTHON_BIN,
                'citations',
                '--brand', $validated['brand'],
                '--domain', $validated['domain'],
                '--provider', $provider,
                '--format', 'json',
            ];
            if (!empty($validated['topic'])) {
                $args[] = '--topic';
                $args[] = $validated['topic'];
            }

            $envKey = self::PROVIDER_ENV_KEYS[$provider] ?? self::PROVIDER_ENV_KEYS[self::DEFAULT_PROVIDER];
            $process = new Process($args, null, [$envKey => $apiKey]);
            $process->setTimeout(120);
            $process->run();

            if (!$process->isSuccessful() && trim($process->getOutput()) === '') {
                throw new ProcessFailedException($process);
            }

            $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

            if (!empty($result['skipped_reason'])) {
                throw new \RuntimeException((string) $result['skipped_reason']);
            }

            $citation->update([
                'provider' => $provider,
                'verdict' => $result['verdict'] ?? null,
                'queries_run' => $result['queries_run'] ?? 0,
                'brand_mention_rate' => (int) round((float) ($result['brand_mention_rate'] ?? 0) * 100),
                'domain_citation_rate' => (int) round((float) ($result['domain_citation_rate'] ?? 0) * 100),
                'entries' => $result['entries'] ?? [],
                'top_cited_domains' => $result['top_cited_domains'] ?? [],
                'status' => 'completed',
            ]);

            return redirect()
                ->route('admin.geo-citations.show', $citation->id)
                ->with('message', '检测完成');
        } catch (\Throwable $e) {
            $citation->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.geo-citations.index')
                ->withErrors(['brand' => '检测失败：'.$e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        GeoCitation::findOrFail($id)->delete();

        return redirect()->route('admin.geo-citations.index')->with('message', '记录已删除');
    }

    private function storedApiKey(): string
    {
        $setting = SiteSetting::where('setting_key', self::SETTING_KEY_API_KEY)->first();
        if (!$setting || !$setting->setting_value) {
            return '';
        }

        return $this->apiKeyCrypto->decrypt((string) $setting->setting_value);
    }

    private function maskedStoredKey(): string
    {
        $setting = SiteSetting::where('setting_key', self::SETTING_KEY_API_KEY)->first();
        if (!$setting || !$setting->setting_value) {
            return '';
        }

        return $this->apiKeyCrypto->mask((string) $setting->setting_value);
    }

    private function storedProvider(): string
    {
        $setting = SiteSetting::where('setting_key', self::SETTING_KEY_PROVIDER)->first();
        $provider = $setting?->setting_value ? (string) $setting->setting_value : self::DEFAULT_PROVIDER;

        return array_key_exists($provider, self::PROVIDER_ENV_KEYS) ? $provider : self::DEFAULT_PROVIDER;
    }
}
