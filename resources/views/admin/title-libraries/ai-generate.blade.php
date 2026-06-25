{{-- 【GEO SaaS 二开覆盖】标题库 AI 生成页：增加「行业包 + 主体」，prompt 由行业包驱动。
     行业列表直接读 config('geo_packs')，无需改控制器。覆盖原文件：resources/views/admin/title-libraries/ai-generate.blade.php --}}
@extends('admin.layouts.app')

@php
    $gfPacks = config('geo_packs', []);
    $gfEn = app()->getLocale() === 'en';
    $gfDefaultSubject = old('subject', \App\Support\AdminWeb::siteName());
@endphp

@section('content')
    <div class="px-4 sm:px-0">
        <div class="mb-8 flex items-center space-x-4">
            <a href="{{ route('admin.title-libraries.detail', ['libraryId' => (int) $library->id]) }}" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.title_ai_generate.page_heading') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ __('admin.title_ai_generate.page_subtitle', ['name' => $library->name]) }}</p>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('admin.title_ai_generate.section.config') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('admin.title_ai_generate.section.config_desc') }}</p>
            </div>
            <form method="POST" action="{{ route('admin.title-libraries.ai-generate.submit', ['libraryId' => (int) $library->id]) }}" class="p-6 space-y-6">
                @csrf

                {{-- GEO SaaS：行业包驱动 prompt --}}
                @if (! empty($gfPacks))
                <div class="rounded-md bg-orange-50 border border-orange-200 px-4 py-3 text-sm text-orange-800 flex items-start gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4 mt-0.5 shrink-0"></i>
                    <span>{{ $gfEn ? 'Industry pack drives the prompt — pick the industry and brand subject; titles follow that industry’s GEO strategy (de-marketing, citable, decision-journey).' : '行业包驱动 prompt——选择行业与品牌主体，标题将按该行业的 GEO 策略生成（去营销腔、可被引用、覆盖决策旅程）。' }}</span>
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if (! empty($gfPacks))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'Industry Pack' : '行业包' }}</label>
                        <select name="pack" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 text-sm">
                            @foreach ($gfPacks as $slug => $pack)
                                <option value="{{ $slug }}" @selected(old('pack') === $slug)>{{ $pack['name'] ?? $slug }}（{{ $slug }}）</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $gfEn ? 'Brand Subject' : '品牌主体' }}</label>
                        <input type="text" name="subject" value="{{ $gfDefaultSubject }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 text-sm" placeholder="{{ $gfEn ? 'e.g. your brand name' : '如：走四方旅游网' }}">
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.title_ai_generate.field.keyword_library') }}</label>
                        <select name="keyword_library_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 text-sm" required>
                            <option value="">{{ __('admin.title_ai_generate.option.select_keyword_library') }}</option>
                            @foreach ($keywordLibraries as $keywordLibrary)
                                <option value="{{ (int) $keywordLibrary->id }}" @selected((int) old('keyword_library_id') === (int) $keywordLibrary->id)>
                                    {{ $keywordLibrary->name }} ({{ (int) ($keywordLibrary->keyword_count ?? 0) }} {{ __('admin.title_ai_generate.option.keyword_count_suffix') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.title_ai_generate.field.ai_model') }}</label>
                        <select name="ai_model_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 text-sm" required>
                            <option value="">{{ __('admin.title_ai_generate.option.select_ai_model') }}</option>
                            @foreach ($aiModels as $aiModel)
                                <option value="{{ (int) $aiModel->id }}" @selected((int) old('ai_model_id') === (int) $aiModel->id)>
                                    {{ $aiModel->name }} ({{ $aiModel->model_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.title_ai_generate.field.count') }}</label>
                        <input type="number" name="title_count" value="{{ old('title_count', 10) }}" min="1" max="50" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.title_ai_generate.field.style') }}</label>
                        <select name="title_style" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 text-sm" required>
                            @foreach (['professional', 'attractive', 'seo', 'creative', 'question'] as $style)
                                <option value="{{ $style }}" @selected(old('title_style', 'professional') === $style)>
                                    {{ __('admin.title_ai_generate.style.'.$style) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('admin.title_ai_generate.field.custom_prompt') }}</label>
                    <textarea name="custom_prompt" rows="4" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 text-sm" placeholder="{{ __('admin.title_ai_generate.placeholder.custom_prompt') }}">{{ old('custom_prompt') }}</textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-md text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
                        <i data-lucide="sparkles" class="w-4 h-4 mr-2"></i>
                        {{ __('admin.title_ai_generate.button.sync') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-6 bg-purple-50 border border-purple-200 rounded-lg p-5">
            <h3 class="text-sm font-semibold text-purple-900">{{ __('admin.title_ai_generate.section.instructions') }}</h3>
            <ul class="mt-3 space-y-1 text-sm text-purple-800">
                <li>{{ __('admin.title_ai_generate.instructions.keyword_library') }}</li>
                <li>{{ __('admin.title_ai_generate.instructions.ai_model') }}</li>
                <li>{{ __('admin.title_ai_generate.instructions.count') }}</li>
                <li>{{ __('admin.title_ai_generate.instructions.style') }}</li>
                <li>{{ __('admin.title_ai_generate.instructions.custom_prompt') }}</li>
                <li>{{ __('admin.title_ai_generate.instructions.saved_titles') }}</li>
            </ul>
        </div>
    </div>
@endsection
