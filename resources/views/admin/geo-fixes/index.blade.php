@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        @include('admin.partials.geo-tools-tabs')
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">GEO 修复建议</h1>
                <p class="mt-1 text-sm text-gray-600">自动生成缺失的 robots.txt / llms.txt / 结构化数据 / Meta 标签等文件内容，免费技术检测，不调用 AI API。生成后仅展示预览，需要你手动复制应用到站点。</p>
            </div>
        </div>

        @if (session('message'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded text-sm">{{ session('message') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
                @foreach ($errors->all() as $err)<div>{{ $err }}</div>@endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">生成修复方案</h3>
                </div>
                <div class="px-6 py-5">
                    <form method="POST" action="{{ route('admin.geo-fixes.store') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">网址</label>
                            <input type="url" name="url" value="{{ old('url', $defaultUrl) }}" required placeholder="https://yoursite.com"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">只生成指定类别（不选则生成全部）</label>
                            <div class="flex flex-wrap gap-4">
                                @foreach ($categories as $key => $label)
                                    <label class="inline-flex items-center text-sm text-gray-700">
                                        <input type="checkbox" name="only[]" value="{{ $key }}" class="rounded border-gray-300 mr-1.5">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i data-lucide="wrench" class="w-4 h-4 mr-2"></i>
                            生成修复方案
                        </button>
                        <p class="text-xs text-gray-500">先跑一次审计再生成方案，通常需要几秒到一分钟。</p>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">最近一次方案</h3>
                </div>
                <div class="px-6 py-5">
                    @if ($latest)
                        <div class="text-2xl font-bold text-gray-900">{{ $latest->score_before }} → {{ $latest->score_estimated_after }}<span class="text-base text-gray-400">/100</span></div>
                        <div class="mt-1 text-sm text-gray-500">{{ count($latest->fixes ?? []) }} 项修复 · {{ $latest->created_at?->diffForHumans() }}</div>
                        <a href="{{ route('admin.geo-fixes.show', $latest->id) }}" class="mt-3 inline-flex items-center text-sm text-blue-600 hover:text-blue-700">
                            查看详情 <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                        </a>
                    @else
                        <p class="text-sm text-gray-500">还没有生成过修复方案。</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">历史记录</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">网址</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">分数变化</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">时间</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($fixes as $fix)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 truncate max-w-xs">{{ $fix->url }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                    {{ $fix->score_before !== null ? $fix->score_before.' → '.$fix->score_estimated_after : '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($fix->status === 'completed')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">已完成</span>
                                    @elseif ($fix->status === 'failed')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">失败</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">进行中</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $fix->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4 text-right text-sm space-x-3">
                                    @if ($fix->status === 'completed')
                                        <a href="{{ route('admin.geo-fixes.show', $fix->id) }}" class="text-blue-600 hover:text-blue-700">查看</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.geo-fixes.delete', $fix->id) }}" class="inline" onsubmit="return confirm('确定删除这条记录？')">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-700">删除</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">暂无记录</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($fixes->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">{{ $fixes->links() }}</div>
            @endif
        </div>
    </div>
@endsection
