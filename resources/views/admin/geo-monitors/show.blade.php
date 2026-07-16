@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center space-x-4 mb-8">
            <a href="{{ route('admin.geo-monitors.index') }}" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">AI 可见度监控详情</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $monitor->domain }}</p>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <div class="text-gray-500">可见度分数</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ $monitor->visibility_score }}/100</div>
                </div>
                <div>
                    <div class="text-gray-500">分数变化</div>
                    <div class="mt-1 font-semibold {{ ($monitor->score_delta ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $monitor->score_delta !== null ? ($monitor->score_delta > 0 ? '+' : '').$monitor->score_delta : 'n/a' }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500">最近一次审计得分</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $monitor->latest_geo_score !== null ? $monitor->latest_geo_score.'/100' : '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">累计快照数</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $monitor->total_snapshots ?? '—' }}</div>
                </div>
            </div>
        </div>

        @if (!empty($monitor->signals))
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">可见度信号</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($monitor->signals as $signal)
                        <div class="px-6 py-3 flex items-center justify-between text-sm">
                            <div>
                                <span class="font-medium text-gray-900">{{ $signal['label'] ?? ($signal['key'] ?? '') }}</span>
                                <span class="ml-2 text-xs text-gray-400">{{ $signal['status'] ?? '' }}</span>
                            </div>
                            <span class="text-gray-600">{{ $signal['score'] ?? 0 }} / {{ $signal['max_score'] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (!empty($monitor->recommendations))
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">建议</h3>
                </div>
                <ul class="px-6 py-4 text-sm text-gray-600 list-disc list-inside space-y-1">
                    @foreach ($monitor->recommendations as $rec)
                        <li>{{ $rec }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
