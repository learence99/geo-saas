{{-- 【GEO SaaS 新增】关键词库 · AI 扩词。核心词 + 行业包 → 写入当前库。
     resources/views/admin/keyword-libraries/ai-expand.blade.php --}}
@extends('admin.layouts.app')

@php
    $gfEn = app()->getLocale() === 'en';
    $gfDefaultSubject = old('subject', \App\Support\AdminWeb::siteName());
@endphp

@section('content')
    <div class="px-4 sm:px-0">
        <div class="mb-8 flex items-center space-x-4">
            <a href="{{ route('admin.keyword-libraries.detail', ['libraryId' => (int) $library->id]) }}" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $gfEn ? 'AI Keyword Expansion' : 'AI 扩词' }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ ($gfEn ? 'Expand a core word into search keywords for: ' : '把核心词扩展成搜索关键词，写入：') }}<span class="font-medium text-gray-900">{{ $library->name }}</span></p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ $gfEn ? 'Generation Config' : '生成配置' }}</h3>
            </div>
            <form method="POST" action="{{ route('admin.keyword-libraries.ai-expand.submit', ['libraryId' => (int) $library->id]) }}" class="p-6 space-y-6">
                @csrf

                <div class="rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800 flex items-start gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4 mt-0.5 shrink-0"></i>
                    <span>{{ $gfEn ? 'Industry pack drives the prompt — keywords follow that industry’s decision journey and de-marketing rules.' : '行业包驱动 prompt——关键词按该行业的决策旅程与去营销腔规则生成。' }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if (! empty($packs))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'Industry Pack' : '行业包' }}</label>
                        <select name="pack" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @foreach ($packs as $slug => $pack)
                                <option value="{{ $slug }}" @selected(old('pack') === $slug)>{{ $pack['name'] ?? $slug }}（{{ $slug }}）</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'Brand Subject' : '品牌主体' }}</label>
                        <input type="text" name="subject" value="{{ $gfDefaultSubject }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'Core Word' : '核心词' }}</label>
                        <input type="text" name="core" value="{{ old('core') }}" required placeholder="{{ $gfEn ? 'e.g. Los Angeles tour' : '如：洛杉矶旅游' }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'AI Model' : 'AI 模型' }}</label>
                        <select name="ai_model_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            <option value="">{{ $gfEn ? 'Select a model' : '选择模型' }}</option>
                            @foreach ($aiModels as $aiModel)
                                <option value="{{ (int) $aiModel->id }}" @selected((int) old('ai_model_id') === (int) $aiModel->id)>{{ $aiModel->name }} ({{ $aiModel->model_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'Count' : '生成数量' }}</label>
                        <input type="number" name="count" value="{{ old('count', 15) }}" min="1" max="50" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i data-lucide="sparkles" class="w-4 h-4 mr-2"></i>
                        {{ $gfEn ? 'Generate & Save' : '生成并写入本库' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
