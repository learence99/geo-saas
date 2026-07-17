@extends('admin.layouts.app')

@php
    $scoreColor = static function (?int $score): string {
        if ($score === null) {
            return 'text-gray-400';
        }
        if ($score >= 90) {
            return 'text-green-600';
        }
        if ($score >= 50) {
            return 'text-amber-600';
        }
        return 'text-red-600';
    };
    $categoryLabels = [
        'performance' => '性能',
        'seo' => 'SEO',
        'accessibility' => '无障碍',
        'best-practices' => '最佳实践',
    ];
@endphp

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center space-x-4 mb-8">
            <a href="{{ route('admin.technical-seo-audits.index') }}" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">技术SEO审计详情</h1>
                <p class="mt-1 text-sm text-gray-600 break-all">{{ $audit->url }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white shadow rounded-lg px-6 py-6 text-center">
                <div class="text-4xl font-bold {{ $scoreColor($audit->performance_score) }}">{{ $audit->performance_score ?? '—' }}</div>
                <div class="mt-1 text-sm text-gray-500">性能</div>
            </div>
            <div class="bg-white shadow rounded-lg px-6 py-6 text-center">
                <div class="text-4xl font-bold {{ $scoreColor($audit->seo_score) }}">{{ $audit->seo_score ?? '—' }}</div>
                <div class="mt-1 text-sm text-gray-500">SEO</div>
            </div>
            <div class="bg-white shadow rounded-lg px-6 py-6 text-center">
                <div class="text-4xl font-bold {{ $scoreColor($audit->accessibility_score) }}">{{ $audit->accessibility_score ?? '—' }}</div>
                <div class="mt-1 text-sm text-gray-500">无障碍</div>
            </div>
            <div class="bg-white shadow rounded-lg px-6 py-6 text-center">
                <div class="text-4xl font-bold {{ $scoreColor($audit->best_practices_score) }}">{{ $audit->best_practices_score ?? '—' }}</div>
                <div class="mt-1 text-sm text-gray-500">最佳实践</div>
            </div>
        </div>

        <div class="text-xs text-gray-400 mb-6">
            审计时间：{{ $audit->created_at?->format('Y-m-d H:i:s') }}
            @if ($audit->lighthouse_version)
                · Lighthouse v{{ $audit->lighthouse_version }}
            @endif
        </div>

        @if (!empty($audit->core_web_vitals))
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">Core Web Vitals</h3>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 px-6 py-5">
                    @foreach ($audit->core_web_vitals as $vital)
                        <div>
                            <div class="text-xs text-gray-500">{{ $vital['title'] ?? '' }}</div>
                            <div class="mt-1 text-lg font-semibold {{ $scoreColor(isset($vital['score']) ? (int) round(($vital['score'] ?? 0) * 100) : null) }}">
                                {{ $vital['display_value'] ?? '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (!empty($audit->issues))
            @foreach ($audit->issues as $categoryKey => $categoryIssues)
                <div class="bg-white shadow rounded-lg mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-base font-medium text-gray-900">{{ $categoryLabels[$categoryKey] ?? $categoryKey }} · {{ count($categoryIssues) }} 项待优化</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($categoryIssues as $issue)
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-sm font-medium text-gray-900">{{ $issue['title'] ?? '' }}</span>
                                    @if (!empty($issue['display_value']))
                                        <span class="text-sm text-amber-600 whitespace-nowrap">{{ $issue['display_value'] }}</span>
                                    @endif
                                </div>
                                @if (!empty($issue['description']))
                                    <p class="mt-1 text-xs text-gray-500">{{ $issue['description'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg text-sm">
                所有维度均未发现明显问题（各项得分均在 90 分以上）。
            </div>
        @endif
    </div>
@endsection
