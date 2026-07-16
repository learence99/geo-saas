@extends('admin.layouts.app')

@php
    $verdictColor = match ($citation->verdict) {
        'strong' => 'text-green-600',
        'cited' => 'text-blue-600',
        'mentioned_only' => 'text-amber-600',
        default => 'text-red-600',
    };
    $verdictLabel = match ($citation->verdict) {
        'strong' => '🏆 强 — AI 在多数回答中引用该域名为来源',
        'cited' => '✅ 被引用 — 出现在来源中，但不稳定',
        'mentioned_only' => '🟡 仅被提及 — AI 知道该品牌，但从未引用该域名',
        default => '❌ 未被发现 — AI 回答中既未提及品牌也未引用域名',
    };
@endphp

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center space-x-4 mb-8">
            <a href="{{ route('admin.geo-citations.index') }}" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">检测详情</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $citation->brand }} · {{ $citation->domain }}</p>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-6">
                <div class="text-xl font-bold {{ $verdictColor }}">{{ $verdictLabel }}</div>
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">提问数量</div>
                        <div class="mt-1 font-semibold text-gray-900">{{ $citation->queries_run }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">品牌提及率</div>
                        <div class="mt-1 font-semibold text-gray-900">{{ $citation->brand_mention_rate }}%</div>
                    </div>
                    <div>
                        <div class="text-gray-500">域名引用率</div>
                        <div class="mt-1 font-semibold text-gray-900">{{ $citation->domain_citation_rate }}%</div>
                    </div>
                    <div>
                        <div class="text-gray-500">检测时间</div>
                        <div class="mt-1 font-semibold text-gray-900">{{ $citation->created_at?->format('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
                @if ($citation->topic)
                    <div class="mt-4 text-sm text-gray-500">主题：{{ $citation->topic }}</div>
                @endif
            </div>
        </div>

        @if (!empty($citation->top_cited_domains))
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">被引用的竞品域名</h3>
                </div>
                <div class="px-6 py-4 flex flex-wrap gap-2">
                    @foreach ($citation->top_cited_domains as $entry)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                            {{ $entry[0] ?? '' }} ({{ $entry[1] ?? 0 }})
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="space-y-4">
            @foreach (($citation->entries ?? []) as $entry)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-900">{{ $entry['query'] ?? '' }}</h3>
                        <span class="text-xs text-gray-400">{{ $entry['platform'] ?? '' }}</span>
                    </div>
                    <div class="px-6 py-4 text-sm space-y-2">
                        @if (!empty($entry['error']))
                            <div class="text-red-600">错误：{{ $entry['error'] }}</div>
                        @else
                            <div class="flex gap-6">
                                <span>品牌提及：{{ !empty($entry['brand_mentioned']) ? '✅' : '—' }}</span>
                                <span>域名引用：{{ !empty($entry['domain_cited']) ? '✅' : '—' }}</span>
                            </div>
                            @if (!empty($entry['cited_sources']))
                                <div class="text-gray-500">
                                    引用来源：
                                    @foreach ($entry['cited_sources'] as $source)
                                        <span class="break-all">{{ $source }}@if (!$loop->last), @endif</span>
                                    @endforeach
                                </div>
                            @endif
                            @if (!empty($entry['snippet']))
                                <div class="text-gray-400 italic">"{{ $entry['snippet'] }}"</div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
