{{-- 标题库管理(自有功能模块)。resources/views/admin/title-workbench/index.blade.php --}}
@extends('admin.layouts.app')

@php
    $valueCls = ['低'=>'bg-gray-100 text-gray-600','中'=>'bg-blue-50 text-blue-700','中高'=>'bg-purple-50 text-purple-700','高'=>'bg-amber-50 text-amber-700','很高'=>'bg-red-50 text-red-700'];
    $statusCls = ['未生成'=>'bg-gray-100 text-gray-600','已生成'=>'bg-blue-50 text-blue-700','待审核'=>'bg-amber-50 text-amber-700','可发布'=>'bg-purple-50 text-purple-700','已发布'=>'bg-green-50 text-green-700'];
@endphp

@section('content')
<div class="px-4 sm:px-0">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">标题库管理</h1>
        <p class="mt-1 text-sm text-gray-500">管理母标题、页面类型、商业价值、生成状态与发布状态。母标题由关键词蒸馏而来。</p>
    </div>

    @if(session('message'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('message') }}</div>
    @endif

    <div class="bg-white shadow rounded-lg">
        <form method="GET" class="flex flex-wrap items-center gap-3 p-4 border-b border-gray-100">
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="搜索母标题 / 核心词 / 来源关键词" class="border-gray-300 rounded-md text-sm w-64">
            <select name="type" class="border-gray-300 rounded-md text-sm"><option value="">全部类型</option>@foreach($pageTypes as $t)<option value="{{ $t }}" @selected($filters['type']===$t)>{{ $t }}</option>@endforeach</select>
            <select name="status" class="border-gray-300 rounded-md text-sm"><option value="">全部状态</option>@foreach($statuses as $s)<option value="{{ $s }}" @selected($filters['status']===$s)>{{ $s }}</option>@endforeach</select>
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm rounded-md">筛选</button>
            <a href="{{ route('admin.title-workbench.index') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">清除</a>
        </form>

        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs">
                        <th class="text-left font-medium px-4 py-3">母标题</th>
                        <th class="text-left font-medium px-4 py-3">页面类型</th>
                        <th class="text-left font-medium px-4 py-3">价值</th>
                        <th class="text-left font-medium px-4 py-3">优先级</th>
                        <th class="text-left font-medium px-4 py-3">状态</th>
                        <th class="text-left font-medium px-4 py-3">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($titles as $t)
                        <tr class="border-b border-gray-100">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $t->title }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">来源:{{ $t->keyword ?: '手动录入' }}@if($t->core_word) · 核心词:{{ $t->core_word }}@endif</div>
                            </td>
                            <td class="px-4 py-3">@if($t->page_type)<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">{{ $t->page_type }}</span>@else<span class="text-gray-300">-</span>@endif</td>
                            <td class="px-4 py-3">@if($t->value)<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $valueCls[$t->value] ?? 'bg-gray-100 text-gray-600' }}">{{ $t->value }}</span>@else<span class="text-gray-300">-</span>@endif</td>
                            <td class="px-4 py-3">@if($t->priority)<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $t->priority==='P1' ? 'bg-red-50 text-red-700' : 'bg-gray-100 text-gray-600' }}">{{ $t->priority }}</span>@else<span class="text-gray-300">-</span>@endif</td>
                            <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusCls[$t->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $t->status ?: '未生成' }}</span></td>
                            <td class="px-4 py-3"><a href="{{ route('admin.tasks.create') }}" class="px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">生成文章</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">暂无母标题。去 <a href="{{ route('admin.keyword-workbench.index') }}" class="text-blue-600">关键词库</a> 点「生成母标题」蒸馏。</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($titles->hasPages())
            <div class="p-4 border-t border-gray-100">{{ $titles->onEachSide(1)->links() }}</div>
        @endif
    </div>
</div>
@endsection
