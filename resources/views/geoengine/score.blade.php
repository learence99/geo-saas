{{-- GEO 内容评分页（自包含，Beacon 风格）。放置：resources/views/geoengine/score.blade.php --}}
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>GEO 内容评分</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
:root{--blue-600:#3568d4;--blue-50:#eef4fe;--blue-700:#2a52b5;--orange-500:#f0863c;--orange-50:#fdf1e6;
--ink:#2a2f39;--ink-2:#595f6b;--ink-3:#828791;--line:#e3e6ea;--line-2:#eef0f3;--bg:#f7f8fb;--surface:#fff;
--pos:#25a06a;--pos-bg:#e7f6ee;--neg:#d24f3c;--neg-bg:#fbeae7;--warn:#b8770f;--r:9px;--r-lg:14px;}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Plus Jakarta Sans',system-ui,sans-serif}
body{background:var(--bg);color:var(--ink);font-size:14px;line-height:1.5;padding:26px}
.wrap{max-width:980px;margin:0 auto}
.mono{font-family:'JetBrains Mono',monospace}
h1{font-size:22px;font-weight:700;letter-spacing:-.02em}
.sub{font-size:13px;color:var(--ink-3);margin-top:3px}
.grid{display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-top:18px;align-items:start}
.card{background:var(--surface);border:1px solid var(--line);border-radius:var(--r-lg);padding:16px}
label{font-size:12px;font-weight:600;color:var(--ink-2);display:block;margin-bottom:6px}
input[type=text]{height:38px;border:1px solid var(--line);border-radius:var(--r);padding:0 11px;font-size:14px;width:100%;font-family:inherit}
textarea{width:100%;min-height:300px;border:1px solid var(--line);border-radius:var(--r);padding:11px;font-size:13px;font-family:inherit;line-height:1.6;resize:vertical}
input:focus,textarea:focus{outline:none;border-color:var(--blue-600);box-shadow:0 0 0 3px var(--blue-50)}
.btn{height:38px;padding:0 18px;border-radius:var(--r);border:1px solid var(--orange-500);background:var(--orange-500);color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit}
.btn:disabled{opacity:.5}
.gauge{display:flex;flex-direction:column;align-items:center;padding:8px 0 14px}
.gauge .ring{width:130px;height:130px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.gauge .hole{width:96px;height:96px;border-radius:50%;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center}
.gauge .hole b{font-size:32px;font-weight:800;letter-spacing:-.02em}
.gauge .hole small{font-size:11px;color:var(--ink-3);font-weight:600}
.chk{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid var(--line-2);align-items:flex-start}
.chk .box{width:18px;height:18px;border-radius:5px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;margin-top:1px}
.chk .ttl{font-size:13px;font-weight:600}
.chk .tip{font-size:12px;color:var(--orange-500);margin-top:2px}
.muted{color:var(--ink-3);font-size:12px}
.spin{display:none;font-size:13px;color:var(--ink-3);margin-top:10px}
</style>
</head>
<body>
<div class="wrap">
  <h1>GEO 内容评分</h1>
  <p class="sub">把文章正文贴进来，按"AI 是否爱引用"打分，并给出优化清单。规则评分，秒出。</p>
  <div class="grid">
    <div class="card">
      <label>核心词（可选，用于判断首段是否直答）</label>
      <input type="text" id="kw" placeholder="如：美国旅游" style="margin-bottom:12px">
      <label>文章正文（支持 Markdown）</label>
      <textarea id="content" placeholder="把生成的文章/正文粘贴到这里…"></textarea>
      <div style="margin-top:12px;display:flex;align-items:center;gap:12px">
        <button class="btn" id="go">评分</button>
        <span class="spin" id="spin">分析中…</span>
        <a href="/geo-engine" style="margin-left:auto;font-size:13px;color:var(--blue-600);text-decoration:none">← 回选词引擎</a>
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
<script>
const $ = (id) => document.getElementById(id);
$('go').addEventListener('click', async () => {
  const content = $('content').value.trim();
  if (!content) { alert('请先粘贴正文'); return; }
  $('go').disabled = true; $('spin').style.display = 'inline';
  try {
    const res = await fetch('/geo-score/run', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
      body: JSON.stringify({ content: content, keyword: $('kw').value.trim() })
    });
    const d = await res.json();
    if (!d.ok) { alert('评分失败：' + (d.error || '')); return; }
    render(d);
  } catch (e) { alert('请求出错：' + e.message); }
  finally { $('go').disabled = false; $('spin').style.display = 'none'; }
});
function render(d){
  $('result').style.display = 'block';
  $('score').textContent = d.score;
  const col = d.score >= 80 ? 'var(--pos)' : d.score >= 60 ? 'var(--orange-500)' : 'var(--neg)';
  const deg = Math.round(d.score / 100 * 360);
  $('ring').style.background = 'conic-gradient(' + col + ' 0 ' + deg + 'deg, var(--line-2) ' + deg + 'deg 360deg)';
  $('score').style.color = col;
  $('grade').textContent = d.grade; $('grade').style.color = col;
  $('passed').textContent = '通过 ' + d.passed + '/' + d.total + ' 项 · ' + d.wordCount + ' 字';
  $('checks').innerHTML = d.checks.map(c =>
    '<div class="chk"><span class="box" style="' + (c.ok ? 'background:var(--pos);color:#fff' : 'background:#fff;border:1.5px solid var(--line);color:transparent') + '">' + (c.ok ? '✓' : '') + '</span>' +
    '<div><div class="ttl" style="' + (c.ok ? 'color:var(--ink-3);text-decoration:line-through' : '') + '">' + c.label + '</div>' +
    (c.tip ? '<div class="tip">' + c.tip + '</div>' : '') + '</div></div>'
  ).join('');
}
</script>
</body>
</html>
