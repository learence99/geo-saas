{{-- 选词引擎（嵌入后台布局）。resources/views/admin/geo-engine/index.blade.php --}}
@extends('admin.layouts.app')

@section('content')
<div class="gft">
  <h1>选词引擎 <span class="pill blue" style="vertical-align:middle">多行业</span></h1>
  <p class="sub">输入核心词 → AI 自动生成问题集群与 GEO 标题，一键入库到素材库（标题库 / 关键词库）。换行业包即换行业。</p>

  <div class="card" style="margin-top:14px">
    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <div class="f"><label>行业包</label>
        <select id="pack">@foreach($packs as $slug => $p)<option value="{{ $slug }}">{{ $p['name'] }}（{{ $slug }}）</option>@endforeach</select>
      </div>
      <div class="f" style="flex:1;min-width:150px"><label>品牌主体</label><input type="text" id="subject" value="走四方旅游网"></div>
      <div class="f" style="flex:1;min-width:150px"><label>核心词</label><input type="text" id="keyword" value="洛杉矶旅游"></div>
      <div class="f"><label>每阶段数量 <span id="cv" class="mono">3</span></label><input type="range" id="count" min="1" max="6" value="3"></div>
      <button class="gbtn org" id="go">生成</button>
    </div>
    <div class="meta" id="routed"></div>
    <div class="spin" id="spin" style="display:none">生成中…（含校验与自动修复，约 10–30 秒）</div>
    <div class="banner" id="banner"></div>
  </div>

  <div id="savebar" style="display:none;align-items:center;gap:10px;background:var(--c-surface);border:1px solid var(--c-line);border-radius:14px;padding:12px 16px;margin-bottom:16px;flex-wrap:wrap">
    <span style="font-size:13px;color:var(--c-ink2);font-weight:600">入库到素材库：</span>
    <select id="saveType"><option value="title">标题库</option><option value="keyword">关键词库</option></select>
    <label class="rad"><input type="radio" name="smode" value="new" checked> 新建库</label>
    <input type="text" id="libname" placeholder="库名（留空用核心词）" style="flex:1;min-width:140px">
    <label class="rad"><input type="radio" name="smode" value="append"> 追加到</label>
    <select id="libsel" disabled style="min-width:150px"></select>
    <button class="gbtn pri" id="saveBtn">入库</button>
    <a href="{{ route('admin.geo-score.index') }}" class="gbtn" style="text-decoration:none">GEO 内容评分 →</a>
  </div>

  <div id="out"></div>
</div>
@endsection

@push('styles')
<style>
.gft{--c-blue:#3568d4;--c-blue50:#eef4fe;--c-blue700:#2a52b5;--c-org:#f0863c;--c-ink:#2a2f39;--c-ink2:#595f6b;--c-ink3:#828791;--c-line:#e3e6ea;--c-line2:#eef0f3;--c-surface:#fff;--c-pos:#25a06a;--c-posbg:#e7f6ee;--c-neg:#d24f3c;--c-negbg:#fbeae7;--c-warn:#b8770f;--c-warnbg:#fdf1e6;color:var(--c-ink)}
.gft .mono{font-family:'JetBrains Mono',monospace}
.gft h1{font-size:22px;font-weight:700;letter-spacing:-.02em;margin:0}
.gft .sub{font-size:13px;color:var(--c-ink3);margin-top:3px}
.gft .card{background:var(--c-surface);border:1px solid var(--c-line);border-radius:14px;padding:16px;margin-bottom:16px}
.gft .f{display:flex;flex-direction:column;gap:5px}
.gft label{font-size:12px;font-weight:600;color:var(--c-ink2)}
.gft input[type=text],.gft select{height:36px;border:1px solid var(--c-line);border-radius:8px;padding:0 11px;font-size:14px;font-family:inherit;background:var(--c-surface);color:var(--c-ink)}
.gft input:focus,.gft select:focus{outline:none;border-color:var(--c-blue);box-shadow:0 0 0 3px var(--c-blue50)}
.gft input[type=range]{width:130px}
.gft .gbtn{height:36px;padding:0 16px;border-radius:8px;border:1px solid var(--c-line);background:var(--c-surface);color:var(--c-ink);font-size:13px;font-weight:600;cursor:pointer;font-family:inherit}
.gft .gbtn.pri{background:var(--c-blue);border-color:var(--c-blue);color:#fff}
.gft .gbtn.org{background:var(--c-org);border-color:var(--c-org);color:#fff}
.gft .gbtn:disabled{opacity:.5}
.gft .rad{font-size:13px;color:var(--c-ink2);display:inline-flex;align-items:center;gap:4px;cursor:pointer}
.gft select:disabled,.gft input:disabled{opacity:.45;background:#f7f8fb}
.gft .meta{font-size:12px;color:var(--c-ink3);margin-top:8px}
.gft .spin{font-size:13px;color:var(--c-ink3);margin-top:10px}
.gft .banner{padding:11px 14px;border-radius:8px;font-size:13px;font-weight:500;margin-top:12px;display:none}
.gft .banner.ok{background:var(--c-posbg);color:var(--c-pos)}
.gft .banner.warn{background:var(--c-warnbg);color:var(--c-warn)}
.gft .banner.err{background:var(--c-negbg);color:var(--c-neg)}
.gft .stage-h{font-size:14px;font-weight:700;margin:18px 0 8px;display:flex;align-items:center;gap:8px}
.gft .stage-h .n{font-size:11px;font-weight:600;color:var(--c-ink3);background:#f7f8fb;border:1px solid var(--c-line);border-radius:20px;padding:1px 8px}
.gft .item{border:1px solid var(--c-line);border-radius:11px;padding:13px 15px;margin-bottom:9px;background:var(--c-surface)}
.gft .item .mv{font-size:15px;font-weight:600;color:var(--c-ink)}
.gft .item .tx{font-size:12.5px;color:var(--c-ink2);margin-top:5px}
.gft .item .ra{font-size:12px;color:var(--c-ink3);margin-top:6px}
.gft .tags{display:flex;gap:6px;margin-bottom:7px;flex-wrap:wrap}
.gft .pill{font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px}
.gft .pill.blue{background:var(--c-blue50);color:var(--c-blue700)}
.gft .t-BRAND{background:#fdf1e6;color:var(--c-org)}.gft .t-PRODUCT{background:var(--c-blue50);color:var(--c-blue700)}.gft .t-BUSINESS{background:#eef0f3;color:var(--c-ink2)}.gft .t-FAQ{background:var(--c-posbg);color:var(--c-pos)}
.gft .i-STRONG{background:var(--c-posbg);color:var(--c-pos)}.gft .i-MEDIUM{background:var(--c-blue50);color:var(--c-blue700)}.gft .i-WEAK,.gft .i-KNOWLEDGE{background:#f7f8fb;color:var(--c-ink3);border:1px solid var(--c-line)}
</style>
@endpush

@push('scripts')
<script>
(function(){
const $ = (id) => document.getElementById(id);
const tok = () => document.querySelector('meta[name=csrf-token]').content;
$('count').addEventListener('input', e => $('cv').textContent = e.target.value);
function showBanner(kind, text){ const b = $('banner'); b.className = 'banner ' + kind; b.innerHTML = text; b.style.display = 'block'; }

$('go').addEventListener('click', async () => {
  const btn = $('go'); btn.disabled = true;
  $('out').innerHTML=''; $('banner').style.display='none'; $('routed').textContent=''; $('savebar').style.display='none'; $('spin').style.display='block';
  try {
    const res = await fetch('{{ route('admin.geo-engine.generate') }}', { method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':tok(),'Accept':'application/json'},
      body: JSON.stringify({ keyword:$('keyword').value.trim(), subject:$('subject').value.trim(), pack:$('pack').value, count:parseInt($('count').value,10) }) });
    const data = await res.json();
    $('spin').style.display='none';
    if(!data.ok){ showBanner('err','生成失败：'+(data.error||'')); return; }
    render(data);
  } catch(err){ $('spin').style.display='none'; showBanner('err','请求出错：'+err.message); } finally { btn.disabled=false; }
});

function esc(s){ const d=document.createElement('div'); d.textContent=s==null?'':s; return d.innerHTML; }
function render(data){
  window.lastResult = data; $('savebar').style.display='flex';
  $('routed').textContent = '行业包：'+data.pack_name+' · 路由策略：'+data.routedType+' · 主体：'+data.subject;
  const rep = data.report;
  const failCount = Object.keys(rep.itemErrors||{}).length + (rep.batchErrors||[]).length;
  if(rep.ok) showBanner('ok','全部 '+rep.total+' 条通过校验'+(data.repairTries?'（经过 '+data.repairTries+' 次自动修复）':''));
  else showBanner('warn','共 '+rep.total+' 条，仍有 '+failCount+' 处未通过（已尽力修复 '+data.repairTries+' 次），标橙为存疑');
  const stages=[]; data.items.forEach(it=>{ if(!stages.includes(it.stage)) stages.push(it.stage); });
  const out=$('out');
  stages.forEach(st=>{
    const group = data.items.map((it,idx)=>({it,idx})).filter(x=>x.it.stage===st);
    const h=document.createElement('div'); h.className='stage-h'; h.innerHTML=st+' <span class="n">'+group.length+' 条</span>'; out.appendChild(h);
    group.forEach(({it,idx})=>{
      const bad = rep.itemErrors && rep.itemErrors[idx];
      const card=document.createElement('div');
      card.innerHTML='<div class="item" style="'+(bad?'background:#fdf6ef;border-left:3px solid var(--c-org)':'')+'">'+
        '<div class="tags"><span class="pill t-'+it.type+'">'+it.type+'</span><span class="pill i-'+it.intent+'">'+it.intent+'</span></div>'+
        '<div class="mv">'+esc(it.merchantVersion)+'</div><div class="tx">用户原问：'+esc(it.text)+'</div><div class="ra">'+esc(it.rationale)+'</div>'+
        (bad?'<div class="ra" style="color:var(--c-org)">⚠ '+bad.join('；')+'</div>':'')+'</div>';
      out.appendChild(card);
    });
  });
}

const LIBS = @json(['title' => $titleLibraries, 'keyword' => $keywordLibraries]);
function fillLibSel(){
  const list = LIBS[$('saveType').value] || [];
  $('libsel').innerHTML = list.length
    ? list.map(l=>'<option value="'+l.id+'">'+esc(l.name)+'</option>').join('')
    : '<option value="">（暂无已有库）</option>';
}
function curMode(){ return document.querySelector('input[name=smode]:checked').value; }
function syncMode(){ const ap = curMode()==='append'; $('libsel').disabled=!ap; $('libname').disabled=ap; }
$('saveType').addEventListener('change', fillLibSel);
document.querySelectorAll('input[name=smode]').forEach(r=>r.addEventListener('change', syncMode));
fillLibSel(); syncMode();

async function saveLib(){
  if(!window.lastResult || !window.lastResult.items){ showBanner('err','请先生成内容'); return; }
  const mode = curMode();
  if(mode==='append' && !$('libsel').value){ showBanner('err','请选择要追加的库（该类型暂无库时请先用"新建库"）'); return; }
  const items = window.lastResult.items.map(it=>({text:it.text, merchantVersion:it.merchantVersion}));
  const btn=$('saveBtn'); btn.disabled=true;
  try {
    const res = await fetch('{{ route('admin.geo-engine.save') }}', { method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':tok(),'Accept':'application/json'},
      body: JSON.stringify({ keyword:window.lastResult.keyword, target:$('saveType').value, mode:mode,
        name:$('libname').value.trim(), library_id: mode==='append'?parseInt($('libsel').value,10):null, items:items }) });
    const data = await res.json();
    if(!data.ok){ showBanner('err','入库失败：'+(data.error||'')); return; }
    showBanner('ok', data.message+' &nbsp;<a href="'+data.url+'" style="color:var(--c-pos);text-decoration:underline">去查看 →</a>');
  } catch(err){ showBanner('err','请求出错：'+err.message); } finally { btn.disabled=false; }
}
$('saveBtn').addEventListener('click', saveLib);
})();
</script>
@endpush
