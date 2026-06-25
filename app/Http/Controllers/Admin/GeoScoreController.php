<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GeoFlow\GeoScorer;
use App\Support\AdminWeb;
use Illuminate\Http\Request;

/**
 * GEO 内容评分。原生后台页：/geo_admin/geo-score（admin.geo-score.*）。
 */
class GeoScoreController extends Controller
{
    public function index()
    {
        return view('admin.geo-score.index', [
            'pageTitle' => '内容评分',
            'activeMenu' => 'geo_score',
            'adminSiteName' => AdminWeb::siteName(),
        ]);
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'content' => 'required|string|max:50000',
            'keyword' => 'nullable|string|max:60',
        ]);

        return response()->json(['ok' => true] + GeoScorer::score($data['content'], $data['keyword'] ?? ''));
    }
}
