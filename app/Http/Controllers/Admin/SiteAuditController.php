<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GeoFlow\SiteAuditService;
use App\Support\AdminWeb;
use Illuminate\Http\Request;

/**
 * 站点体检（GEO 诊断）。后台页：/geo_admin/site-audit（admin.site-audit.*）。
 * 客户输入 URL → 跑 SEO 基础 + GEO/AI 可见 检查 → 出问题清单 + 评分（纯 PHP，零 AI 成本）。
 */
class SiteAuditController extends Controller
{
    public function __construct(private readonly SiteAuditService $service)
    {
    }

    public function index()
    {
        return view('admin.site-audit.index', [
            'pageTitle' => '站点体检',
            'activeMenu' => 'site_audit',
            'adminSiteName' => AdminWeb::siteName(),
        ]);
    }

    public function run(Request $request)
    {
        $data = $request->validate(['url' => 'required|string|max:300']);

        try {
            $report = $this->service->audit($data['url']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => '体检失败：' . $e->getMessage()], 422);
        }

        return response()->json($report);
    }
}
