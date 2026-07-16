@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center space-x-4 mb-8">
            <a href="{{ route('admin.geo-perceptions.index') }}" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">AI 认知快照详情</h1>
                <p class="mt-1 text-sm text-gray-600 break-all">{{ $perception->url }}</p>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded text-sm mb-6">
            ⚠️ 本快照为基于页面信号的确定性推算，并非真实 AI 模型的回答。想看真实模型怎么说，请用「AI引用检测」。
        </div>

        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <div class="text-gray-500">品牌</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $perception->brand_name ?? '—' }}{{ $perception->brand_entity_type ? ' ('.$perception->brand_entity_type.')' : '' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">可信度等级</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $perception->citability_grade ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">信任分</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $perception->trust_score !== null ? $perception->trust_score.'/100' : '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">主要话题</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $perception->main_topic ?? '—' }}</div>
                </div>
            </div>
            @if ($perception->ai_readable_summary)
                <div class="px-6 pb-6 text-sm text-gray-600 border-t border-gray-100 pt-4">{{ $perception->ai_readable_summary }}</div>
            @endif
        </div>

        @if (!empty($perception->schema_types_present))
            <div class="mb-6">
                <div class="flex flex-wrap gap-2">
                    @foreach ($perception->schema_types_present as $type)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $type }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @if (!empty($perception->citation_worthy_facts))
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-sm font-medium text-gray-900">✅ 可被引用的信号</h3></div>
                    <ul class="px-6 py-4 text-sm text-gray-600 list-disc list-inside space-y-1">
                        @foreach ($perception->citation_worthy_facts as $item)<li>{{ $item }}</li>@endforeach
                    </ul>
                </div>
            @endif
            @if (!empty($perception->supported_claims))
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-sm font-medium text-gray-900">✅ 有依据支撑的表述</h3></div>
                    <ul class="px-6 py-4 text-sm text-gray-600 list-disc list-inside space-y-1">
                        @foreach ($perception->supported_claims as $item)<li>{{ $item }}</li>@endforeach
                    </ul>
                </div>
            @endif
            @if (!empty($perception->unsupported_claims))
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-sm font-medium text-gray-900">⚠️ 缺乏页面依据的表述</h3></div>
                    <ul class="px-6 py-4 text-sm text-gray-600 list-disc list-inside space-y-1">
                        @foreach ($perception->unsupported_claims as $item)<li>{{ $item }}</li>@endforeach
                    </ul>
                </div>
            @endif
            @if (!empty($perception->missing_authority_signals))
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-sm font-medium text-gray-900">🚫 缺失的权威信号</h3></div>
                    <ul class="px-6 py-4 text-sm text-gray-600 list-disc list-inside space-y-1">
                        @foreach ($perception->missing_authority_signals as $item)<li>{{ $item }}</li>@endforeach
                    </ul>
                </div>
            @endif
            @if (!empty($perception->detected_services))
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-sm font-medium text-gray-900">检测到的服务/产品</h3></div>
                    <ul class="px-6 py-4 text-sm text-gray-600 list-disc list-inside space-y-1">
                        @foreach ($perception->detected_services as $item)<li>{{ $item }}</li>@endforeach
                    </ul>
                </div>
            @endif
            @if (!empty($perception->ambiguities))
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200"><h3 class="text-sm font-medium text-gray-900">存在歧义之处</h3></div>
                    <ul class="px-6 py-4 text-sm text-gray-600 list-disc list-inside space-y-1">
                        @foreach ($perception->ambiguities as $item)<li>{{ $item }}</li>@endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection
