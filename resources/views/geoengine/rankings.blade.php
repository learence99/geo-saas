{{-- AI 排名追踪（MVP，自包含 Beacon 风格）。放置：resources/views/geoengine/rankings.blade.php --}}
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>AI 排名追踪</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
:root{--blue-600:#3568d4;--blue-50:#eef4fe;--blue-700:#2a52b5;--orange-500:#f0863c;--orange-50:#fdf1e6;
--ink:#2a2f39;--ink-2:#595f6b;--ink-3:#828791;--line:#e3e6ea;--line-2:#eef0f3;--bg:#f7f8fb;--surface:#fff;
--pos:#25a06a;--pos-bg:#e7f6ee;--neg:#d24f3c;--neg-bg:#fbeae7;--r:9px;--r-lg:14px;}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Plus Jakarta Sans',system-ui,sans-serif}
body{background:var(--bg);color:var(--ink);font-size:14px;line-height:1.5;padding:26px}
.wrap{max-width:1080px;margin:0 auto}
.mono{font-family:'JetBrains Mono',monospace;font-variant-numeric:tabular-nums}
h1{font-size:22px;font-weight:700;letter-spacing:-.02em}
.sub{font-size:13px;color:var(--ink-3);margin-top:3px}
.metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:18px 0}
.metric{background:var(--surface);border:1px solid var(--line);border-radius:var(--r-lg);padding:14px 16px}
.metric .lbl{font-size:12.5px;color:var(--ink-3);font-weight:600}
.metric .val{font-size:26px;font-weight:700;letter-spacing:-.02em;margin-top:4px}
.card{background:var(--surface);border:1px solid var(--line);border-radius:var(--r-lg);padding:16px;margin-bottom:16px}
label{font-size:12px;font-weight:600;color:var(--ink-2)}
input[type=text],select{height:36px;border:1px solid var(--line);border-radius:8px;padding:0 10px;font-size:13px;font-family:inherit;background:var(--surface)}
input:focus,select:focus{outline:none;border-color:var(--blue-600);box-shadow:0 0 0 3px var(--blue-50)}
.btn{height:36px;padding:0 15px;border-radius:8px;border:1px solid var(--line);background:var(--surface);color:var(--ink);font-size:13px;font-weight:600;cursor:pointer;font-family:inherit}
.btn-pri{background:var(--blue-600);border-color:var(--blue-600);color:#fff}
.btn-org{background:var(--orange-500);border-color:var(--orange-500);color:#fff}
.btn:disabled{opacity:.5}
table{width:100%;border-collapse:collapse;font-size:13px}
th{text-align:left;font-size:11px;font-weight:600;color:var(--ink-3);text-transform:uppercase;letter-spacing:.05em;padding:0 12px 10px;border-bottom:1px solid var(--line)}
td{padding:12px;border-bottom:1px solid var(--line-2);color:var(--ink-2);vertical-align:middle}
td.nm{color:var(--ink);font-weight:600}
.pill{font-size:11.5px;font-weight:600;padding:2px 9px;border-radius:20px;white-space:nowrap}
.pill.green{background:var(--pos-bg);color:var(--pos)}.pill.gray{background:var(--bg);color:var(--ink-3);border:1px solid var(--line)}.pill.blue{background:var(--blue-50);color:var(--blue-700)}
.empty{text-align:center;color:var(--ink-3);padding:30px;font-size:13px}
</style>
</head>
<body>
<div class="wrap">
  <h1>AI 排名追踪 <span class="pill blue" style="vertical-align:middle">MVP</span></h1>
  <p class="sub">拿你的问题去"问"AI 引擎，解析答案里有没有引用你、排第几。这是真数据采集，非示例。</p>

  <div class="metrics">
    <div class="metric"><div class="lbl">追踪 Prompt</div><div class="val mono">{{ $metrics['tracked'] }}</div></div>
    <div class="metric"><div class="lbl">已被引用</div><div class="val mono" style="color:var(--pos)">{{ $metrics['cited'] }}</div></div>
    <div class="metric"><div class="lbl">平均排名</div><div class="val mono">{{ $metrics['avg_rank'] ?? '—' }}</div></div>
  </div>

  <div class="card">
    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <div style="display:flex;flex-direction:column;gap:5px"><label>品牌主体</label><input type="text" id="subject" value="走四方旅游网" style="width:150px"></div>
      <div style="display:flex;flex-direction:column;gap:5px;flex:1;min-width:220px"><label>要追踪的问题(Prompt)</label><input type="text" id="prompt" placeholder="如：推荐一个靠谱的洛杉矶旅游团" style="width:100%"></div>
      <div style="display:flex;flex-direction:column;gap:5px"><label>引擎</label>
        <select id="engine">@foreach($engines as $k => $e)<option value="{{ $k }}">{{ $e['name'] }}</option>@endforeach</select>
      </div>
      <button class="btn btn-pri" id="add">添加追踪</button>
      <button class="btn btn-org" id="checkAll">全部检查</button>
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
          <td class="muted" style="font-size:12px;color:var(--ink-3)">{{ $r['snap']['checked_at'] ?? '' }}</td>
          <td><button class="btn checkOne" style="height:30px">检查</button></td>
        </tr>
        @empty
        <tr><td colspan="8" class="empty">还没有追踪的 Prompt，上面添加一个试试。</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <a href="/geo-engine" style="font-size:13px;color:var(--blue-600);text-decoration:none">← 回选词引擎</a>
</div>
<script>
const $ = (id) => document.getElementById(id);
const tok = () => document.querySelector('meta[name=csrf-token]').content;
function showMsg(t, ok){ const m=$('msg'); m.style.display='block'; m.style.color=ok?'var(--pos)':'var(--neg)'; m.textContent=t; }

$('add').addEventListener('click', async () => {
  const prompt = $('prompt').value.trim(); if(!prompt){ showMsg('请输入要追踪的问题', false); return; }
  $('add').disabled = true;
  try {
    const res = await fetch('/ranking-tracker/add', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':tok(),'Accept':'application/json'},
      body: JSON.stringify({ subject:$('subject').value.trim(), prompt:prompt, engine:$('engine').value }) });
    const d = await res.json();
    if(d.ok){ location.reload(); } else { showMsg('添加失败：'+(d.error||''), false); }
  } catch(e){ showMsg('出错：'+e.message, false); } finally { $('add').disabled=false; }
});

async function checkRow(tr){
  const id = tr.dataset.id; const btn = tr.querySelector('.checkOne'); btn.disabled = true; btn.textContent='检查中…';
  try {
    const res = await fetch('/ranking-tracker/check', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':tok(),'Accept':'application/json'}, body: JSON.stringify({ id: parseInt(id,10) }) });
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
  $('checkAll').disabled = false;
  showMsg('全部检查完成', true);
});
</script>
</body>
</html>
