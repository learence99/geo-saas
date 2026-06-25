{{-- 【GEO SaaS 覆盖】关键词库 创建/编辑表单。创建模式下增加「用 AI 生成并创建」卡片（行业包驱动，一步建库+填词）。
     覆盖原文件：resources/views/admin/keyword-libraries/form.blade.php --}}
@extends('admin.layouts.app')

@php
    $formAction = $isEdit
        ? route('admin.keyword-libraries.update', ['libraryId' => (int) $libraryId])
        : route('admin.keyword-libraries.store');
    $gfEn = app()->getLocale() === 'en';
    $gfPacks = config('geo_packs', []);
    $gfDefaultSubject = old('subject', \App\Support\AdminWeb::siteName());
    $gfModels = $isEdit ? collect() : \App\Models\AiModel::query()
        ->select(['id', 'name', 'model_id'])
        ->where('status', 'active')
        ->whereRaw("COALESCE(NULLIF(model_type, ''), 'chat') = 'chat'")
        ->orderBy('name')->get();
@endphp

@section('content')
    <div class="px-4 sm:px-0">
        <div class="mb-8 flex items-center space-x-4">
            <a href="{{ route('admin.keyword-libraries.index') }}" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $isEdit ? __('admin.button.edit') : __('admin.keyword_libraries.modal_create') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('admin.keyword_libraries.subtitle') }}</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        {{-- 创建模式：AI 生成并创建（行业包驱动，一步到位） --}}
        @if (! $isEdit && ! empty($gfPacks))
        <div class="bg-white shadow rounded-lg mb-6 border-t-4 border-purple-500">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-2">
                <i data-lucide="sparkles" class="w-5 h-5 text-purple-600"></i>
                <h3 class="text-lg font-medium text-gray-900">{{ $gfEn ? 'Create with AI' : '用 AI 生成并创建' }}</h3>
                <span class="text-xs text-purple-700 bg-purple-50 border border-purple-200 rounded-full px-2 py-0.5">{{ $gfEn ? 'recommended' : '推荐' }}</span>
            </div>
            <div class="px-6 py-6">
                <p class="text-sm text-gray-600 mb-4">{{ $gfEn ? 'Give a name + a core word; AI generates keywords by the industry pack’s decision journey and writes them into a new library — one step.' : '填库名 + 核心词，按行业包决策旅程一键生成关键词并写入新库——无需先建空库。' }}</p>
                <form method="POST" action="{{ route('admin.keyword-libraries.ai-new') }}" class="space-y-5">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'Library Name' : '库名称' }} <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required value="{{ old('name') }}" placeholder="{{ $gfEn ? 'e.g. LA Tour Keywords' : '如：洛杉矶旅游词库' }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'Core Word' : '核心词' }} <span class="text-red-500">*</span></label>
                            <input type="text" name="core" required value="{{ old('core') }}" placeholder="{{ $gfEn ? 'e.g. Los Angeles tour' : '如：洛杉矶旅游' }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'Industry Pack' : '行业包' }}</label>
                            <select name="pack" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                @foreach ($gfPacks as $slug => $pack)
                                    <option value="{{ $slug }}" @selected(old('pack') === $slug)>{{ $pack['name'] ?? $slug }}（{{ $slug }}）</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'Brand Subject' : '品牌主体' }}</label>
                            <input type="text" name="subject" value="{{ $gfDefaultSubject }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'AI Model' : 'AI 模型' }} <span class="text-red-500">*</span></label>
                            <select name="ai_model_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                <option value="">{{ $gfEn ? 'Select a model' : '选择模型' }}</option>
                                @foreach ($gfModels as $m)
                                    <option value="{{ (int) $m->id }}" @selected((int) old('ai_model_id') === (int) $m->id)>{{ $m->name }} ({{ $m->model_id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'Count' : '生成数量' }}</label>
                            <input type="number" name="count" value="{{ old('count', 15) }}" min="1" max="50" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                            <i data-lucide="sparkles" class="w-4 h-4 mr-2"></i>
                            {{ $gfEn ? 'Generate & Create Library' : 'AI 生成并创建库' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="relative my-6 text-center">
            <span class="bg-gray-50 px-3 text-xs text-gray-400 relative z-10">{{ $gfEn ? 'or create an empty library' : '或 手动创建空库' }}</span>
            <div class="absolute inset-x-0 top-1/2 border-t border-gray-200"></div>
        </div>
        @endif

        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-6">
                <form method="POST" action="{{ $formAction }}" class="space-y-6">
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.keyword_libraries.field_name') }}</label>
                        <input type="text" name="name" required value="{{ old('name', (string) ($libraryForm['name'] ?? '')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="{{ __('admin.keyword_libraries.placeholder_name') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.keyword_libraries.field_description') }}</label>
                        <textarea name="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="{{ __('admin.keyword_libraries.placeholder_description') }}">{{ old('description', (string) ($libraryForm['description'] ?? '')) }}</textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('admin.keyword-libraries.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            {{ __('admin.button.cancel') }}
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            {{ __('admin.button.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
