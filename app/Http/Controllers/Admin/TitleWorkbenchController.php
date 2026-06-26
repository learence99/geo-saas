<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Title;
use App\Services\GeoFlow\TitleDistillService;
use App\Support\AdminWeb;
use Illuminate\Http\Request;

/**
 * 标题库管理(自有功能模块)。母标题列表 + 页面类型/价值/优先级/状态 + 来源关键词血缘。
 * 跑在原生 titles 表(加法扩列)上。下游"生成文章"走原生任务管线。
 */
class TitleWorkbenchController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $status = (string) $request->query('status', '');

        $q = Title::query()->orderByDesc('id');
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")
                    ->orWhere('core_word', 'like', "%{$search}%")
                    ->orWhere('keyword', 'like', "%{$search}%");
            });
        }
        if ($type !== '') { $q->where('page_type', $type); }
        if ($status !== '') { $q->where('status', $status); }

        $titles = $q->paginate(30)->withQueryString();

        return view('admin.title-workbench.index', [
            'pageTitle' => '标题库管理',
            'activeMenu' => 'title_workbench',
            'adminSiteName' => AdminWeb::siteName(),
            'titles' => $titles,
            'pageTypes' => TitleDistillService::PAGE_TYPES,
            'statuses' => ['未生成', '已生成', '待审核', '可发布', '已发布'],
            'filters' => compact('search', 'type', 'status'),
        ]);
    }
}
