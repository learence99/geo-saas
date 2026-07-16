@extends('admin.layouts.app')

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center space-x-4 mb-8">
            <a href="{{ route('admin.geo-fixes.index') }}" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">修复方案详情</h1>
                <p class="mt-1 text-sm text-gray-600 break-all">{{ $fix->url }}</p>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-6 flex items-center gap-8">
                <div class="text-3xl font-bold text-gray-900">
                    {{ $fix->score_before }} <span class="text-gray-400 text-lg">→</span> {{ $fix->score_estimated_after }}<span class="text-lg text-gray-400">/100</span>
                </div>
                <div class="text-sm text-gray-500">
                    <div>{{ count($fix->fixes ?? []) }} 项修复建议</div>
                    <div class="mt-1">生成时间：{{ $fix->created_at?->format('Y-m-d H:i:s') }}</div>
                </div>
            </div>
        </div>

        @if (!empty($fix->skipped))
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">已跳过（无需修复或无法自动生成）</h3>
                </div>
                <ul class="px-6 py-4 text-sm text-gray-500 list-disc list-inside space-y-1">
                    @foreach ($fix->skipped as $s)
                        <li>{{ $s }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-4">
            @foreach (($fix->fixes ?? []) as $i => $item)
                @php
                    $actionLabel = match ($item['action'] ?? '') {
                        'create' => '新建文件',
                        'append' => '追加内容',
                        'snippet' => '插入代码片段',
                        default => $item['action'] ?? '',
                    };
                @endphp
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">{{ $item['description'] ?? '' }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $item['file_name'] ?? '' }} · {{ $actionLabel }}</p>
                        </div>
                        <button type="button" onclick="copyFixContent({{ $i }})" class="text-xs px-3 py-1.5 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">
                            复制内容
                        </button>
                    </div>
                    <div class="px-6 py-4">
                        <pre id="fix-content-{{ $i }}" class="text-xs bg-gray-50 rounded-md p-4 overflow-x-auto whitespace-pre-wrap break-all">{{ $item['content'] ?? '' }}</pre>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        function copyFixContent(index) {
            const el = document.getElementById('fix-content-' + index);
            if (!el) return;
            navigator.clipboard.writeText(el.textContent).then(() => {
                const btn = event.target;
                const original = btn.textContent;
                btn.textContent = '已复制';
                setTimeout(() => { btn.textContent = original; }, 1500);
            });
        }
    </script>
@endsection
