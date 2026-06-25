{{-- GEO 内容评分（嵌入后台布局）。resources/views/admin/geo-score/index.blade.php --}}
@extends('admin.layouts.app')

@section('content')
<div class="gft">
  <h1>GEO 内容评分</h1>
  <p class="sub">把文章正文贴进来，按"AI 是否爱引用"打分，并给出优化清单。规则评分，秒出。</p>
  <div class="grid">
    <div class="card">
      <label>核心词（可选，用于判断首段是否直答）</label>
      <input type="text" id="kw" placeholder="如：美国旅游" style="margin-bottom:12px">
      <label>文章正文（支持 Markdown）</label>
      <textarea id="content" placeholder="把生成的文章/正文粘贴到这里…"></textarea>
      <div style="margin-top:12px;display:flex;align-items:center;gap:12px">
        <button class="gbtn org" id="go">评分</button>
        <span class="spin" id="spin" style="display:none">分析中…</span>
      </div>
    </div>
    <div class="card" id="result" style="display:none">
      <div class="gauge">
        <div class="ring" id="ring"><div class="hole"><b id="score">0</b><small>/ 100</small></div></div>
        <div style="margin-top:10px;font-size:13px"><span id="grade" style="font-weight:700"></span> · <span class="muted" id="passed"></span></div>
      </div>
      <div style="font-size:13px;font-weight:700;margin:6px 0 4px">优化清单</div>
      <div id="checks"></div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
.gft{--c-blue:#3568d4;--c-blue50:#eef4fe;--c-org:#f0863c;--c-ink:#2a2f39;--c-ink2:#595f6b;--c-ink3:#828791;--c-line:#e3e6ea;--c-line2:#eef0f3;--c-surface:#fff;--c-pos:#25a06a;--c-neg:#d24f3c;color:var(--c-ink)}
.gft h1{font-size:22px;font-weight:700;letter-spacing:-.02em;margin:0}
.gft .sub{font-size:13px;color:var(--c-ink3);margin-top:3px}
.gft .grid{display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-top:16px;align-items:start}
.gft .card{background:var(--c-surface);border:1px solid var(--c-line);border-radius:14px;padding:16px}
.gft label{font-size:12px;font-weight:600;color:var(--c-ink2);display:block;margin-bottom:6px}
.gft input[type=text]{height:38px;border:1px solid var(--c-line);border-radius:8px;padding:0 11px;font-size:14px;width:100%;font-family:inherit;background:var(--c-surface);color:var(--c-ink)}
.gft textarea{width:100%;min-height:300px;border:1px solid var(--c-line);border-radius:8px;padding:11px;font-size:13px;font-family:inherit;line-height:1.6;resize:vertical;background:var(--c-surface);color:var(--c-ink)}
.gft input:focus,.gft textarea:focus{outline:none;border-color:var(--c-blue);box-shadow:0 0 0 3px var(--c-blue50)}
.gft .gbtn{height:38px;padding:0 18px;border-radius:8px;border:1px solid var(--c-org);background:var(--c-org);color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit}
.gft .gbtn:disabled{opacity:.5}
.gft .spin{font-size:13px;color:var(--c-ink3)}
.gft .gauge{display:flex;flex-direction:column;align-items:center;padding:8px 0 14px}
.gft .gauge .ring{width:130px;height:130px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.gft .gauge .hole{width:96px;height:96px;border-radius:50%;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center}
.gft .gauge .hole b{font-size:32px;font-weight:800;letter-spacing:-.02em}
.gft .gauge .hole small{font-size:11px;color:var(--c-ink3);font-weight:600}
.gft .muted{color:var(--c-ink3);font-size:12px}
.gft .chk{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid var(--c-line2);align-items:flex-start}
.gft .chk .box{width:18px;height:18px;border-radius:5px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;margin-top:1px}
.gft .chk .ttl{font-size:13px;font-weight:600}
.gft .chk .tip{font-size:12px;color:var(--c-org);margin-top:2px}
</style>
@endpush

@push('scripts')
<script>
(function(){
const $ = (id) => document.getElementById(id);
$('go').addEventListener('click', async () => {
  const content = $('content').value.trim(); if(!content){ alert('请先粘贴正文'); return; }
  $('go').disabled=true; $('spin').style.display='inline';
  try {
    const res = await fetch('{{ route('admin.geo-score.run') }}', { method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},
      body: JSON.stringify({ content:content, keyword:$('kw').value.trim() }) });
    const d = await res.json();
    if(!d.ok){ alert('评分失败：'+(d.error||'')); return; }
    render(d);
  } catch(e){ alert('请求出错：'+e.message); } finally { $('go').disabled=false; $('spin').style.display='none'; }
});
function render(d){
  $('result').style.display='block';
  $('score').textContent=d.score;
  const col = d.score>=80?'var(--c-pos)':d.score>=60?'var(--c-org)':'var(--c-neg)';
  const deg = Math.round(d.score/100*360);
  $('ring').style.background='conic-gradient('+col+' 0 '+deg+'deg, var(--c-line2) '+deg+'deg 360deg)';
  $('score').style.color=col; $('grade').textContent=d.grade; $('grade').style.color=col;
  $('passed').textContent='通过 '+d.passed+'/'+d.total+' 项 · '+d.wordCount+' 字';
  $('checks').innerHTML = d.checks.map(c =>
    '<div class="chk"><span class="box" style="'+(c.ok?'background:var(--c-pos);color:#fff':'background:#fff;border:1.5px solid var(--c-line);color:transparent')+'">'+(c.ok?'✓':'')+'</span>'+
    '<div><div class="ttl" style="'+(c.ok?'color:var(--c-ink3);text-decoration:line-through':'')+'">'+c.label+'</div>'+
    (c.tip?'<div class="tip">'+c.tip+'</div>':'')+'</div></div>').join('');
}
})();
</script>
@endpush
