{{-- 关键词库管理(自有功能模块)。resources/views/admin/keyword-workbench/index.blade.php --}}
@extends('admin.layouts.app')

@php
    $intentCls = ['信息型'=>'bg-blue-50 text-blue-700','决策型'=>'bg-amber-50 text-amber-700','交易型'=>'bg-red-50 text-red-700','风险型'=>'bg-purple-50 text-purple-700','品牌型'=>'bg-green-50 text-green-700'];
    $valueCls = ['低'=>'bg-gray-100 text-gray-600','中'=>'bg-blue-50 text-blue-700','中高'=>'bg-purple-50 text-purple-700','高'=>'bg-amber-50 text-amber-700','很高'=>'bg-red-50 text-red-700'];
    $statusCls = ['待处理'=>'bg-amber-50 text-amber-700','已生成标题'=>'bg-green-50 text-green-700'];
@endphp

@section('content')
<div class="px-4 sm:px-0">
    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">关键词库管理</h1>
            <p class="mt-1 text-sm text-gray-500">管理用户搜索词、问题词、咨询词,作为 GEO 内容生产原材料。默认模型:<b>{{ $defaultModel ?: '未配置' }}</b></p>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="document.getElementById('gjwManual').classList.remove('hidden')" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">手动录入</button>
            <button type="button" onclick="document.getElementById('gjwGen').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i data-lucide="sparkles" class="w-4 h-4 mr-1.5"></i>新增关键词(AI生成)
            </button>
        </div>
    </div>

    @if(session('message'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('message') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white shadow rounded-lg">
        <form method="GET" class="flex flex-wrap items-center gap-3 p-4 border-b border-gray-100">
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="搜索关键词 / 主题 / 核心词" class="border-gray-300 rounded-md text-sm w-56">
            <select name="intent" class="border-gray-300 rounded-md text-sm"><option value="">全部意图</option>@foreach($intents as $i)<option value="{{ $i }}" @selected($filters['intent']===$i)>{{ $i }}</option>@endforeach</select>
            <select name="value" class="border-gray-300 rounded-md text-sm"><option value="">全部价值</option>@foreach($values as $v)<option value="{{ $v }}" @selected($filters['value']===$v)>{{ $v }}</option>@endforeach</select>
            <select name="status" class="border-gray-300 rounded-md text-sm"><option value="">全部状态</option><option value="待处理" @selected($filters['status']==='待处理')>待处理</option><option value="已生成标题" @selected($filters['status']==='已生成标题')>已生成标题</option></select>
            <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm rounded-md">筛选</button>
            <a href="{{ route('admin.keyword-workbench.index') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">清除</a>
        </form>

        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs">
                        <th class="text-left font-medium px-4 py-3">关键词</th>
                        <th class="text-left font-medium px-4 py-3">核心词</th>
                        <th class="text-left font-medium px-4 py-3">主题</th>
                        <th class="text-left font-medium px-4 py-3">意图</th>
                        <th class="text-left font-medium px-4 py-3">阶段</th>
                        <th class="text-left font-medium px-4 py-3">价值</th>
                        <th class="text-left font-medium px-4 py-3">状态</th>
                        <th class="text-left font-medium px-4 py-3">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($keywords as $k)
                        <tr class="border-b border-gray-100" data-id="{{ $k->id }}">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $k->keyword }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $k->core_word ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $k->category ?: '-' }}</td>
                            <td class="px-4 py-3">@if($k->intent)<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $intentCls[$k->intent] ?? 'bg-gray-100 text-gray-600' }}">{{ $k->intent }}</span>@else<span class="text-gray-300">-</span>@endif</td>
                            <td class="px-4 py-3 text-gray-600">{{ $k->stage ?: '-' }}</td>
                            <td class="px-4 py-3">@if($k->value)<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $valueCls[$k->value] ?? 'bg-gray-100 text-gray-600' }}">{{ $k->value }}</span>@else<span class="text-gray-300">-</span>@endif</td>
                            <td class="px-4 py-3"><span class="c-status inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusCls[$k->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $k->status ?: '待处理' }}</span></td>
                            <td class="px-4 py-3"><button type="button" class="gjw-title px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">生成母标题</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">暂无关键词,点右上角「新增关键词(AI生成)」开始</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($keywords->hasPages())
            <div class="p-4 border-t border-gray-100">{{ $keywords->onEachSide(1)->links() }}</div>
        @endif
    </div>
</div>

{{-- AI 生成弹窗 --}}
<div id="gjwGen" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 overflow-y-auto">
    <div class="relative top-16 mx-auto w-[640px] max-w-[94vw] bg-white rounded-xl shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100"><h3 class="text-lg font-medium">新增关键词(AI 生成)</h3><button type="button" onclick="document.getElementById('gjwGen').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button></div>
        <form method="POST" action="{{ route('admin.keyword-workbench.generate') }}" class="px-6 py-5">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">行业包</label><select name="pack" class="block w-full border-gray-300 rounded-md text-sm">@foreach($packs as $slug=>$p)<option value="{{ $slug }}">{{ $p['name'] ?? $slug }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">品牌主体</label><input type="text" name="subject" value="走四方旅游网" class="block w-full border-gray-300 rounded-md text-sm"></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">核心词</label><input type="text" name="core" required placeholder="如:美国跟团游" class="block w-full border-gray-300 rounded-md text-sm"></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">关键词数</label><input type="number" name="count" value="12" min="1" max="30" class="block w-full border-gray-300 rounded-md text-sm"></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">意图偏向(可选)</label><select name="intent" class="block w-full border-gray-300 rounded-md text-sm"><option value="">全旅程覆盖</option>@foreach($intents as $i)<option value="{{ $i }}">{{ $i }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">价值偏向(可选)</label><select name="value" class="block w-full border-gray-300 rounded-md text-sm"><option value="">不限</option>@foreach($values as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></div>
            </div>
            <p class="mt-3 text-xs text-gray-400">模型走系统默认生成模型,无需选择。每个词由 AI 自动判定意图/阶段/价值/主题。</p>
            <div class="mt-5 flex justify-end gap-3"><button type="button" onclick="document.getElementById('gjwGen').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-md text-sm">取消</button><button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md text-sm font-medium">生成并入库</button></div>
        </form>
    </div>
</div>

{{-- 手动录入弹窗 --}}
<div id="gjwManual" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 overflow-y-auto">
    <div class="relative top-16 mx-auto w-[640px] max-w-[94vw] bg-white rounded-xl shadow-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100"><h3 class="text-lg font-medium">手动录入关键词</h3><button type="button" onclick="document.getElementById('gjwManual').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button></div>
        <form method="POST" action="{{ route('admin.keyword-workbench.manual') }}" class="px-6 py-5">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2"><label class="block text-xs font-medium text-gray-700 mb-1.5">关键词</label><input type="text" name="keyword" required placeholder="如:美国旅游团取消能退吗" class="block w-full border-gray-300 rounded-md text-sm"></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">核心词</label><input type="text" name="core_word" placeholder="如:美国跟团游" class="block w-full border-gray-300 rounded-md text-sm"></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">行业包</label><select name="pack" class="block w-full border-gray-300 rounded-md text-sm">@foreach($packs as $slug=>$p)<option value="{{ $slug }}">{{ $p['name'] ?? $slug }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">主题</label><input type="text" name="category" placeholder="如:退改风险" class="block w-full border-gray-300 rounded-md text-sm"></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">意图</label><select name="intent" class="block w-full border-gray-300 rounded-md text-sm">@foreach($intents as $i)<option value="{{ $i }}">{{ $i }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">阶段</label><select name="stage" class="block w-full border-gray-300 rounded-md text-sm">@foreach($stages as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-medium text-gray-700 mb-1.5">商业价值</label><select name="value" class="block w-full border-gray-300 rounded-md text-sm">@foreach($values as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></div>
            </div>
            <div class="mt-5 flex justify-end gap-3"><button type="button" onclick="document.getElementById('gjwManual').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-md text-sm">取消</button><button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md text-sm font-medium">保存</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const tok = document.querySelector('meta[name=csrf-token]')?.content || '';
  document.querySelectorAll('.gjw-title').forEach(b => b.addEventListener('click', async function(){
    const tr = b.closest('tr'); const id = tr.dataset.id; const orig = b.textContent;
    b.disabled = true; b.textContent = '蒸馏中…';
    try {
      const res = await fetch('{{ route('admin.keyword-workbench.distill') }}', {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':tok,'Accept':'application/json'},body:JSON.stringify({id:parseInt(id,10)})});
      const d = await res.json();
      if(d.ok){
        const s = tr.querySelector('.c-status'); s.textContent='已生成标题'; s.className='c-status inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700';
        b.textContent = '已生成';
        alert('已蒸馏母标题:\n「' + d.title + '」\n页面类型:' + d.page_type + (d.fallback_used ? '\n(AI 不可用,已用模板兜底)' : '') + '\n\n可到左侧「标题库」查看。');
      } else { alert('生成失败:' + (d.error || '')); b.textContent = orig; b.disabled = false; }
    } catch(e){ alert('请求出错:' + e.message); b.textContent = orig; b.disabled = false; }
  }));
})();
</script>
@endpush
