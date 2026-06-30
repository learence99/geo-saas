{{-- AI 排名追踪（嵌入后台布局，带侧栏/顶栏）。放置：resources/views/geoengine/rankings.blade.php --}}
@extends('admin.layouts.app')

@section('content')
<div class="gft">
  <div style="display:flex;align-items:flex-start;margin-bottom:6px">
    <div>
      <h1>AI 可见度 <span class="pill blue" style="vertical-align:middle">MVP</span></h1>
      <p class="sub">拿你的问题去"问"AI 引擎，解析答案里有没有引用你、排第几、提了哪些竞品。真数据采集，非示例。</p>
    </div>
  </div>

  <div class="metrics">
    <div class="metric"><div class="lbl">追踪 Prompt</div><div class="val mono">{{ $metrics['tracked'] }}</div></div>
    <div class="metric"><div class="lbl">已被引用</div><div class="val mono" style="color:var(--c-pos)">{{ $metrics['cited'] }}</div></div>
    <div class="metric"><div class="lbl">平均排名</div><div class="val mono">{{ $metrics['avg_rank'] ?? '—' }}</div></div>
  </div>

  <div class="card">
    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <div style="display:flex;flex-direction:column;gap:5px"><label>品牌主体</label><input type="text" id="subject" value="走四方旅游网" style="width:150px"></div>
      <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:220px"><label>要追踪的问题(Prompt)</label><input type="text" id="prompt" placeholder="如：推荐一个靠谱的洛杉矶旅游团" style="width:100%"></div>
      <div style="display:flex;flex-direction:column;gap:5px"><label>引擎</label>
        <select id="engine">@foreach($engines as $k => $e)<option value="{{ $k }}">{{ $e['name'] }}</option>@endforeach</select>
      </div>
      <button class="gbtn pri" id="add">添加追踪</button>
      <button class="gbtn org" id="checkAll">全部检查</button>
    </div>
    <div id="msg" style="font-size:12.5px;margin-top:10px;display:none"></div>
  </div>

  <div class="card" style="padding:6px 16px 8px">
    <table>
      <thead><tr><th>Prompt</th><th>主体</th><th>引擎</th><th>被引用?</th><th>排名</th><th>提到的竞品</th><th>检查时间</th><th></th></tr></thead>
      <tbody id="tbody">
        @forelse($rows as $r)
        <tr data-id="{{ $r['id'] }}">
          <td class="nm" style="max-width:240px">{{ $r['prompt'] }}</td>
          <td>{{ $r['subject'] }}</td>
          <td>{{ $engines[$r['engine']]['name'] ?? $r['engine'] }}</td>
          <td class="c-cited">@if($r['snap'])<span class="pill {{ $r['snap']['is_cited']?'green':'gray' }}">{{ $r['snap']['is_cited']?'已引用':'未引用' }}</span>@else<span class="pill gray">未检查</span>@endif</td>
          <td class="c-rank mono">{{ $r['snap'] && $r['snap']['rank']!==null ? '#'.$r['snap']['rank'] : '—' }}</td>
          <td class="c-comp" style="max-width:200px;font-size:12px">{{ $r['snap'] ? implode('、', array_slice($r['snap']['competitors'],0,4)) : '' }}</td>
          <td style="font-size:12px;color:var(--c-ink3)">{{ $r['snap']['checked_at'] ?? '' }}</td>
          <td><button class="gbtn checkOne" style="height:30px">检查</button></td>
        </tr>
        @empty
        <tr><td colspan="8" class="empty">还没有追踪的 Prompt，上面添加一个试试。</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

@push('styles')
<style>
.gft{--c-blue:#3568d4;--c-blue50:#eef4fe;--c-blue700:#2a52b5;--c-org:#f0863c;--c-ink:#2a2f39;--c-ink2:#595f6b;--c-ink3:#828791;--c-line:#e3e6ea;--c-line2:#eef0f3;--c-surface:#fff;--c-pos:#25a06a;--c-posbg:#e7f6ee;--c-neg:#d24f3c;color:var(--c-ink)}
.gft .mono{font-family:'JetBrains Mono',monospace;font-variant-numeric:tabular-nums}
.gft h1{font-size:22px;font-weight:700;letter-spacing:-.02em;margin:0}
.gft .sub{font-size:13px;color:var(--c-ink3);margin-top:3px}
.gft .metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:16px 0}
.gft .metric{background:var(--c-surface);border:1px solid var(--c-line);border-radius:14px;padding:14px 16px}
.gft .metric .lbl{font-size:12.5px;color:var(--c-ink3);font-weight:600}
.gft .metric .val{font-size:26px;font-weight:700;letter-spacing:-.02em;margin-top:4px}
.gft .card{background:var(--c-surface);border:1px solid var(--c-line);border-radius:14px;padding:16px;margin-bottom:16px}
.gft label{font-size:12px;font-weight:600;color:var(--c-ink2)}
.gft input[type=text],.gft select{height:36px;border:1px solid var(--c-line);border-radius:8px;padding:0 10px;font-size:13px;font-family:inherit;background:var(--c-surface);color:var(--c-ink)}
.gft input:focus,.gft select:focus{outline:none;border-color:var(--c-blue);box-shadow:0 0 0 3px var(--c-blue50)}
.gft .gbtn{height:36px;padding:0 15px;border-radius:8px;border:1px solid var(--c-line);background:var(--c-surface);color:var(--c-ink);font-size:13px;font-weight:600;cursor:pointer;font-family:inherit}
.gft .gbtn.pri{background:var(--c-blue);border-color:var(--c-blue);color:#fff}
.gft .gbtn.org{background:var(--c-org);border-color:var(--c-org);color:#fff}
.gft .gbtn:disabled{opacity:.5}
.gft table{width:100%;border-collapse:collapse;font-size:13px}
.gft th{text-align:left;font-size:11px;font-weight:600;color:var(--c-ink3);text-transform:uppercase;letter-spacing:.05em;padding:0 12px 10px;border-bottom:1px solid var(--c-line)}
.gft td{padding:12px;border-bottom:1px solid var(--c-line2);color:var(--c-ink2);vertical-align:middle}
.gft td.nm{color:var(--c-ink);font-weight:600}
.gft .pill{font-size:11.5px;font-weight:600;padding:2px 9px;border-radius:20px;white-space:nowrap}
.gft .pill.green{background:var(--c-posbg);color:var(--c-pos)}
.gft .pill.gray{background:#f7f8fb;color:var(--c-ink3);border:1px solid var(--c-line)}
.gft .pill.blue{background:var(--c-blue50);color:var(--c-blue700)}
.gft .empty{text-align:center;color:var(--c-ink3);padding:30px;font-size:13px}
</style>
@endpush

@push('scripts')
<script>
(function(){
const $ = (id) => document.getElementById(id);
const tok = () => document.querySelector('meta[name=csrf-token]').content;
function showMsg(t, ok){ const m=$('msg'); m.style.display='block'; m.style.color=ok?'#25a06a':'#d24f3c'; m.textContent=t; }
$('add').addEventListener('click', async () => {
  const prompt = $('prompt').value.trim(); if(!prompt){ showMsg('请输入要追踪的问题', false); return; }
  $('add').disabled = true;
  try {
    const res = await fetch('{{ route('admin.ranking-tracker.add') }}', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':tok(),'Accept':'application/json'},
      body: JSON.stringify({ subject:$('subject').value.trim(), prompt:prompt, engine:$('engine').value }) });
    const d = await res.json();
    if(d.ok){ location.reload(); } else { showMsg('添加失败：'+(d.error||''), false); }
  } catch(e){ showMsg('出错：'+e.message, false); } finally { $('add').disabled=false; }
});
async function checkRow(tr){
  const id = tr.dataset.id; const btn = tr.querySelector('.checkOne'); btn.disabled = true; btn.textContent='检查中…';
  try {
    const res = await fetch('{{ route('admin.ranking-tracker.check') }}', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':tok(),'Accept':'application/json'}, body: JSON.stringify({ id: parseInt(id,10) }) });
    const d = await res.json();
    if(!d.ok){ showMsg('检查失败：'+(d.error||''), false); return; }
    tr.querySelector('.c-cited').innerHTML = '<span class="pill '+(d.is_cited?'green':'gray')+'">'+(d.is_cited?'已引用':'未引用')+'</span>';
    tr.querySelector('.c-rank').textContent = d.rank!=null ? '#'+d.rank : '—';
    tr.querySelector('.c-comp').textContent = (d.competitors||[]).slice(0,4).join('、');
  } catch(e){ showMsg('出错：'+e.message, false); } finally { btn.disabled=false; btn.textContent='检查'; }
}
document.querySelectorAll('.checkOne').forEach(b => b.addEventListener('click', e => checkRow(e.target.closest('tr'))));
$('checkAll').addEventListener('click', async () => {
  const trs = [...document.querySelectorAll('#tbody tr[data-id]')];
  $('checkAll').disabled = true;
  for(const tr of trs){ await checkRow(tr); }
  $('checkAll').disabled = false; showMsg('全部检查完成', true);
});
})();
</script>
@endpush
