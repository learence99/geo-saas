@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        @include('admin.partials.geo-tools-tabs')

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">技术SEO审计</h1>
                <p class="mt-1 text-sm text-gray-600">基于 Google Lighthouse（Google 官方引擎，PageSpeed Insights 背后用的就是它）检测性能、SEO、无障碍、最佳实践四个维度，包含 Core Web Vitals。免费技术检测，不调用 AI API。</p>
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
                    <h3 class="text-lg font-medium text-gray-900">发起新的审计</h3>
                </div>
                <div class="px-6 py-5">
                    <form method="POST" action="{{ route('admin.technical-seo-audits.store') }}" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <input
                            type="url"
                            name="url"
                            value="{{ old('url', $defaultUrl) }}"
                            required
                            placeholder="https://yoursite.com"
                            class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                        <button type="submit" class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 whitespace-nowrap">
                            <i data-lucide="gauge" class="w-4 h-4 mr-2"></i>
                            开始审计
                        </button>
                    </form>
                    <p class="mt-3 text-xs text-gray-500">审计需要真实打开一次页面测速，通常需要 20~60 秒，提交后请耐心等待页面刷新。</p>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">最近一次得分</h3>
                </div>
                <div class="px-6 py-5">
                    @if ($latest)
                        <div class="grid grid-cols-2 gap-3 text-center">
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ $latest->performance_score ?? '—' }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">性能</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ $latest->seo_score ?? '—' }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">SEO</div>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-500 text-center">{{ $latest->created_at?->diffForHumans() }}</div>
                        <a href="{{ route('admin.technical-seo-audits.show', $latest->id) }}" class="mt-3 flex items-center justify-center text-sm text-blue-600 hover:text-blue-700">
                            查看详情 <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                        </a>
                    @else
                        <p class="text-sm text-gray-500">还没有审计记录，先在左边发起一次。</p>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">性能</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SEO</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">时间</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($audits as $audit)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 truncate max-w-xs">{{ $audit->url }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $audit->performance_score ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $audit->seo_score ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if ($audit->status === 'completed')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">已完成</span>
                                    @elseif ($audit->status === 'failed')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">失败</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">进行中</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $audit->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4 text-right text-sm space-x-3">
                                    @if ($audit->status === 'completed')
                                        <a href="{{ route('admin.technical-seo-audits.show', $audit->id) }}" class="text-blue-600 hover:text-blue-700">查看</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.technical-seo-audits.delete', $audit->id) }}" class="inline" onsubmit="return confirm('确定删除这条记录？')">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-700">删除</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">暂无审计记录</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($audits->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $audits->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
