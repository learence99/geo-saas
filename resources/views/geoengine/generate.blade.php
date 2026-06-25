{{-- 放置：项目根 /resources/views/geoengine/generate.blade.php
     自包含整页（Beacon 风格，内联 CSS），不依赖 GEOFlow 布局，不需要 npm build。 --}}
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>GEO 问题集群生成 · 多行业引擎</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
:root{--blue-600:#3568d4;--blue-50:#eef4fe;--blue-700:#2a52b5;--orange-500:#f0863c;--orange-50:#fdf1e6;
--ink:#2a2f39;--ink-2:#595f6b;--ink-3:#828791;--line:#e3e6ea;--line-2:#eef0f3;--bg:#f7f8fb;--surface:#fff;
--pos:#25a06a;--pos-bg:#e7f6ee;--neg:#d24f3c;--neg-bg:#fbeae7;--warn:#b8770f;--warn-bg:#fdf1e6;--r:9px;--r-lg:14px;}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Plus Jakarta Sans',system-ui,sans-serif}
body{background:var(--bg);color:var(--ink);font-size:14px;line-height:1.5;padding:26px}
.wrap{max-width:920px;margin:0 auto}
.mono{font-family:'JetBrains Mono',monospace}
h1{font-size:22px;font-weight:700;letter-spacing:-.02em}
.sub{font-size:13px;color:var(--ink-3);margin-top:3px}
.card{background:var(--surface);border:1px solid var(--line);border-radius:var(--r-lg);padding:18px;margin-top:18px}
.row{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
.f{display:flex;flex-direction:column;gap:6px}
label{font-size:12px;font-weight:600;color:var(--ink-2)}
input[type=text],select{height:38px;border:1px solid var(--line);border-radius:var(--r);padding:0 11px;font-size:14px;font-family:inherit;background:var(--surface);color:var(--ink)}
input[type=text]:focus,select:focus{outline:none;border-color:var(--blue-600);box-shadow:0 0 0 3px var(--blue-50)}
input[type=range]{width:140px}
.btn{height:38px;padding:0 18px;border-radius:var(--r);border:1px solid var(--orange-500);background:var(--orange-500);color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit}
.btn:disabled{opacity:.5;cursor:not-allowed}
.banner{padding:11px 14px;border-radius:var(--r);font-size:13px;font-weight:500;margin-top:14px;display:none}
.banner.ok{background:var(--pos-bg);color:var(--pos)}
.banner.warn{background:var(--warn-bg);color:var(--warn)}
.banner.err{background:var(--neg-bg);color:var(--neg)}
.stage-h{font-size:14px;font-weight:700;margin:18px 0 8px;color:var(--ink);display:flex;align-items:center;gap:8px}
.stage-h .n{font-size:11px;font-weight:600;color:var(--ink-3);background:var(--bg);border:1px solid var(--line);border-radius:20px;padding:1px 8px}
.item{border:1px solid var(--line);border-radius:11px;padding:13px 15px;margin-bottom:9px}
.item .mv{font-size:15px;font-weight:600;color:var(--ink);letter-spacing:-.01em}
.item .tx{font-size:12.5px;color:var(--ink-2);margin-top:5px}
.item .ra{font-size:12px;color:var(--ink-3);margin-top:6px}
.tags{display:flex;gap:6px;margin-bottom:7px;flex-wrap:wrap}
.pill{font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px}
.t-BRAND{background:var(--orange-50);color:var(--orange-500)}
.t-PRODUCT{background:var(--blue-50);color:var(--blue-700)}
.t-BUSINESS{background:#eef0f3;color:var(--ink-2)}
.t-FAQ{background:var(--pos-bg);color:var(--pos)}
.i-STRONG{background:var(--pos-bg);color:var(--pos)}
.i-MEDIUM{background:var(--blue-50);color:var(--blue-700)}
.i-WEAK{background:var(--bg);color:var(--ink-3);border:1px solid var(--line)}
.i-KNOWLEDGE{background:var(--bg);color:var(--ink-3);border:1px solid var(--line)}
.spin{display:none;font-size:13px;color:var(--ink-3);margin-top:14px}
.meta{font-size:12px;color:var(--ink-3);margin-top:6px}
</style>
</head>
<body>
<div class="wrap">
  <h1>GEO 问题集群生成 · 多行业引擎</h1>
  <p class="sub">输入核心词 → 按用户决策旅程自动生成问题集群与 GEO 标题。换行业包即换行业,引擎不变。</p>

  <div class="card">
    <div class="row">
      <div class="f"><label>行业包</label>
        <select id="pack">
          @foreach($packs as $slug => $p)
            <option value="{{ $slug }}">{{ $p['name'] }}（{{ $slug }}）</option>
          @endforeach
        </select>
      </div>
      <div class="f" style="flex:1;min-width:160px"><label>品牌主体</label>
        <input type="text" id="subject" value="走四方旅游网" placeholder="如：走四方旅游网 / 某医院">
      </div>
      <div class="f" style="flex:1;min-width:160px"><label>核心词</label>
        <input type="text" id="keyword" value="洛杉矶旅游" placeholder="如：洛杉矶旅游团 / 美国签证">
      </div>
      <div class="f"><label>每阶段数量 <span id="cv" class="mono">3</span></label>
        <input type="range" id="count" min="1" max="6" value="3">
      </div>
      <button class="btn" id="go">生成</button>
    </div>
    <div class="meta" id="routed"></div>
    <div class="spin" id="spin">生成中…（含校验与自动修复,约 10–30 秒）</div>
    <div class="banner" id="banner"></div>
  </div>

  <div id="out"></div>
</div>

<script>
const $ = (id) => document.getElementById(id);
$('count').addEventListener('input', e => $('cv').textContent = e.target.value);

$('go').addEventListener('click', async () => {
  const btn = $('go'); btn.disabled = true;
  $('out').innerHTML = ''; $('banner').style.display = 'none'; $('routed').textContent = '';
  $('spin').style.display = 'block';
  try {
    const res = await fetch('/geo-engine/generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        keyword: $('keyword').value.trim(),
        subject: $('subject').value.trim(),
        pack: $('pack').value,
        count: parseInt($('count').value, 10)
      })
    });
    const data = await res.json();
    $('spin').style.display = 'none';
    if (!data.ok) { showBanner('err', '生成失败：' + (data.error || JSON.stringify(data))); return; }
    render(data);
  } catch (err) {
    $('spin').style.display = 'none';
    showBanner('err', '请求出错：' + err.message);
  } finally { btn.disabled = false; }
});

function showBanner(kind, text){ const b = $('banner'); b.className = 'banner ' + kind; b.textContent = text; b.style.display = 'block'; }

function render(data){
  $('routed').textContent = '行业包：' + data.pack_name + ' · 路由策略：' + data.routedType + ' · 主体：' + data.subject;
  const rep = data.report;
  const failCount = Object.keys(rep.itemErrors || {}).length + (rep.batchErrors || []).length;
  if (rep.ok) showBanner('ok', '全部 ' + rep.total + ' 条通过校验' + (data.repairTries ? '（经过 ' + data.repairTries + ' 次自动修复）' : ''));
  else showBanner('warn', '共 ' + rep.total + ' 条,仍有 ' + failCount + ' 处未通过(已尽力修复 ' + data.repairTries + ' 次),下方标橙的为存疑项');

  const stages = [];
  data.items.forEach(it => { if (!stages.includes(it.stage)) stages.push(it.stage); });
  const out = $('out');
  stages.forEach(st => {
    const group = data.items.map((it, idx) => ({ it, idx })).filter(x => x.it.stage === st);
    const h = document.createElement('div');
    h.className = 'stage-h';
    h.innerHTML = st + ' <span class="n">' + group.length + ' 条</span>';
    out.appendChild(h);
    group.forEach(({ it, idx }) => {
      const bad = rep.itemErrors && rep.itemErrors[idx];
      const card = document.createElement('div');
      card.className = 'card'; card.style.marginTop = '0'; card.style.padding = '0';
      card.innerHTML =
        '<div class="item" style="border:0;margin:0;' + (bad ? 'background:#fdf6ef;border-left:3px solid var(--orange-500);border-radius:0' : '') + '">' +
          '<div class="tags"><span class="pill t-' + it.type + '">' + it.type + '</span>' +
          '<span class="pill i-' + it.intent + '">' + it.intent + '</span></div>' +
          '<div class="mv">' + esc(it.merchantVersion) + '</div>' +
          '<div class="tx">用户原问：' + esc(it.text) + '</div>' +
          '<div class="ra">' + esc(it.rationale) + '</div>' +
          (bad ? '<div class="ra" style="color:var(--orange-500)">⚠ ' + bad.join('；') + '</div>' : '') +
        '</div>';
      out.appendChild(card);
    });
  });
}

function esc(s){ const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
</script>
</body>
</html>
