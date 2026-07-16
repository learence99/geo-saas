@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        @include('admin.partials.geo-tools-tabs')
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">AI 可见度监控</h1>
                <p class="mt-1 text-sm text-gray-600">被动检测整站在 AI 引擎眼中的可见度信号，并与上一次快照对比给出分数变化。免费技术检测，不调用 AI API，可定期手动触发以跟踪趋势。</p>
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
                    <h3 class="text-lg font-medium text-gray-900">记录新快照</h3>
                </div>
                <div class="px-6 py-5">
                    <form method="POST" action="{{ route('admin.geo-monitors.store') }}" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <input type="text" name="domain" value="{{ old('domain', $defaultDomain) }}" required placeholder="example.com"
                            class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <button type="submit" class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 whitespace-nowrap">
                            <i data-lucide="radar" class="w-4 h-4 mr-2"></i>
                            记录快照
                        </button>
                    </form>
                    <p class="mt-3 text-xs text-gray-500">每次记录会与历史快照对比，多记录几次才能看到分数变化趋势。</p>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">最近一次结果</h3>
                </div>
                <div class="px-6 py-5">
                    @if ($latest)
                        <div class="text-3xl font-bold text-gray-900">{{ $latest->visibility_score }}<span class="text-base text-gray-400">/100</span></div>
                        <div class="mt-1 text-sm text-gray-500">
                            {{ ucfirst((string) $latest->band) }}
                            @if ($latest->score_delta !== null)
                                · 变化 {{ $latest->score_delta > 0 ? '+' : '' }}{{ $latest->score_delta }}
                            @endif
                        </div>
                        <a href="{{ route('admin.geo-monitors.show', $latest->id) }}" class="mt-3 inline-flex items-center text-sm text-blue-600 hover:text-blue-700">
                            查看详情 <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                        </a>
                    @else
                        <p class="text-sm text-gray-500">还没有监控记录。</p>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">域名</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">可见度分数</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">时间</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($monitors as $monitor)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 truncate max-w-xs">{{ $monitor->domain }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                    {{ $monitor->visibility_score !== null ? $monitor->visibility_score.'/100' : '—' }}
                                    @if ($monitor->score_delta !== null)
                                        <span class="text-xs {{ $monitor->score_delta >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            ({{ $monitor->score_delta > 0 ? '+' : '' }}{{ $monitor->score_delta }})
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($monitor->status === 'completed')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">已完成</span>
                                    @elseif ($monitor->status === 'failed')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">失败</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">进行中</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $monitor->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4 text-right text-sm space-x-3">
                                    @if ($monitor->status === 'completed')
                                        <a href="{{ route('admin.geo-monitors.show', $monitor->id) }}" class="text-blue-600 hover:text-blue-700">查看</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.geo-monitors.delete', $monitor->id) }}" class="inline" onsubmit="return confirm('确定删除这条记录？')">
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
            @if ($monitors->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">{{ $monitors->links() }}</div>
            @endif
        </div>
    </div>
@endsection
