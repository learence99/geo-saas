@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">AI 品牌引用检测</h1>
                <p class="mt-1 text-sm text-gray-600">向真实的 AI 答案引擎提问，检测它是否提及你的品牌、是否把你的域名作为引用来源。每次检测会调用你配置的 API Key，产生真实费用。</p>
            </div>
        </div>

        @if (session('message'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded text-sm">
                {{ session('message') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
                @foreach ($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">API 设置</h3>
                <p class="mt-1 text-sm text-gray-600">全局统一 Key，检测调用产生的费用由平台承担。密钥加密存储，后台只展示掩码。</p>
            </div>
            <div class="px-6 py-5">
                <form method="POST" action="{{ route('admin.geo-citations.settings') }}" class="flex flex-col sm:flex-row gap-3">
                    @csrf
                    <select name="provider" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        @foreach ($providers as $p)
                            <option value="{{ $p }}" @selected($currentProvider === $p)>{{ strtoupper($p) }}</option>
                        @endforeach
                    </select>
                    <input
                        type="password"
                        name="api_key"
                        placeholder="{{ $hasApiKey ? '已配置：'.$apiKeyMasked.'（输入新值可覆盖）' : 'sk-xxxxxxxxxxxxxxxx' }}"
                        class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                    <button type="submit" class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-800 hover:bg-gray-900 whitespace-nowrap">
                        保存
                    </button>
                </form>
                <p class="mt-3 text-xs text-gray-500">
                    默认使用 OpenAI（parametric 模型，只能判断 AI 是否"知道"你的品牌，不返回真实引用来源）。
                    如需真实网页引用来源，推荐切换到 Perplexity（<a href="https://www.perplexity.ai/settings/api" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-700">perplexity.ai/settings/api</a>）。
                    OpenAI Key 可在 <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-700">platform.openai.com/api-keys</a> 获取。不配置则无法发起检测。
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">发起新的检测</h3>
                </div>
                <div class="px-6 py-5">
                    <form method="POST" action="{{ route('admin.geo-citations.store') }}" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">品牌名称</label>
                                <input type="text" name="brand" value="{{ old('brand', $defaultBrand) }}" required
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">域名</label>
                                <input type="text" name="domain" value="{{ old('domain', $defaultDomain) }}" required placeholder="example.com"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">主题（可选，默认使用品牌名生成问题）</label>
                            <input type="text" name="topic" value="{{ old('topic') }}" placeholder="例如：跨境旅行保险"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center px-5 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i data-lucide="play" class="w-4 h-4 mr-2"></i>
                            开始检测
                        </button>
                        <p class="text-xs text-gray-500">检测会向 AI 提出 3 个真实问题并分析回答，通常需要 10~30 秒。</p>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">最近一次结果</h3>
                </div>
                <div class="px-6 py-5">
                    @if ($latest)
                        @php
                            $verdictColor = match ($latest->verdict) {
                                'strong' => 'text-green-600',
                                'cited' => 'text-blue-600',
                                'mentioned_only' => 'text-amber-600',
                                default => 'text-red-600',
                            };
                            $verdictLabel = match ($latest->verdict) {
                                'strong' => '强 — 稳定被引用',
                                'cited' => '被引用 — 不稳定',
                                'mentioned_only' => '仅被提及，未被引用',
                                default => '未被发现',
                            };
                        @endphp
                        <div class="text-xl font-bold {{ $verdictColor }}">{{ $verdictLabel }}</div>
                        <div class="mt-1 text-sm text-gray-500">引用率 {{ $latest->domain_citation_rate }}% · 提及率 {{ $latest->brand_mention_rate }}%</div>
                        <a href="{{ route('admin.geo-citations.show', $latest->id) }}" class="mt-3 inline-flex items-center text-sm text-blue-600 hover:text-blue-700">
                            查看详情 <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                        </a>
                    @else
                        <p class="text-sm text-gray-500">还没有检测记录。</p>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">品牌 / 域名</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">结论</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">时间</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($citations as $citation)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 truncate max-w-xs">{{ $citation->brand }} · {{ $citation->domain }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                    {{ $citation->verdict ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($citation->status === 'completed')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">已完成</span>
                                    @elseif ($citation->status === 'failed')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">失败</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">进行中</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $citation->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4 text-right text-sm space-x-3">
                                    @if ($citation->status === 'completed')
                                        <a href="{{ route('admin.geo-citations.show', $citation->id) }}" class="text-blue-600 hover:text-blue-700">查看</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.geo-citations.delete', $citation->id) }}" class="inline" onsubmit="return confirm('确定删除这条记录？')">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-700">删除</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">暂无检测记录</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($citations->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $citations->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
