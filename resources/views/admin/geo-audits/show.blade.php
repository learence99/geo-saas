@extends('admin.layouts.app')

@php
    $checkLabels = [
        'robots_txt' => 'Robots.txt（AI 爬虫访问规则）',
        'llms_txt' => 'llms.txt',
        'schema_jsonld' => 'JSON-LD 结构化数据',
        'meta_tags' => 'Meta 标签',
        'content' => '内容质量',
        'signals' => '其他信号',
        'ai_discovery' => 'AI Discovery（.well-known 等）',
        'brand_entity' => '品牌与实体一致性',
    ];
    $bandColor = match ($audit->band) {
        'excellent' => 'text-green-600',
        'good' => 'text-blue-600',
        'foundation' => 'text-amber-600',
        default => 'text-red-600',
    };
@endphp

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center space-x-4 mb-8">
            <a href="{{ route('admin.geo-audits.index') }}" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">审计详情</h1>
                <p class="mt-1 text-sm text-gray-600 break-all">{{ $audit->url }}</p>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-6 flex items-center gap-8">
                <div>
                    <div class="text-5xl font-bold {{ $bandColor }}">{{ $audit->score }}<span class="text-xl text-gray-400">/100</span></div>
                    <div class="mt-1 text-sm text-gray-500">{{ ucfirst((string) $audit->band) }}</div>
                </div>
                <div class="text-sm text-gray-500">
                    <div>审计时间：{{ $audit->created_at?->format('Y-m-d H:i:s') }}</div>
                    <div class="mt-1">发起人：{{ $audit->triggeredBy?->name ?? $audit->triggeredBy?->username ?? '—' }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach (($audit->checks ?? []) as $key => $check)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-base font-medium text-gray-900">{{ $checkLabels[$key] ?? $key }}</h3>
                        <span class="text-sm font-semibold {{ ($check['passed'] ?? false) ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $check['score'] ?? 0 }} / {{ $check['max'] ?? 0 }}
                        </span>
                    </div>
                    <div class="px-6 py-4">
                        <dl class="text-sm text-gray-600 space-y-1">
                            @foreach (($check['details'] ?? []) as $detailKey => $detailValue)
                                @if (!is_array($detailValue))
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">{{ $detailKey }}</dt>
                                        <dd class="text-gray-900 text-right break-all">
                                            @if (is_bool($detailValue))
                                                {{ $detailValue ? '✅' : '—' }}
                                            @else
                                                {{ $detailValue === '' || $detailValue === null ? '—' : $detailValue }}
                                            @endif
                                        </dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
