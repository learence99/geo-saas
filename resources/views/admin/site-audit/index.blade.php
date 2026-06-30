{{-- 站点体检 / GEO 诊断（嵌入后台布局）。放置：resources/views/admin/site-audit/index.blade.php --}}
@extends('admin.layouts.app')

@section('content')
<div class="sa">
  <div class="sa-head">
    <h1>站点体检 <span class="sa-pill">GEO 诊断</span></h1>
    <p class="sa-sub">输入任意网址，一键检查 SEO 基础与 AI 可见度问题——纯规则检测，秒出，零成本。</p>
  </div>

  <div class="sa-card">
    <div class="sa-form">
      <input type="text" id="sa-url" placeholder="example.com 或 https://example.com" autocomplete="off">
      <button id="sa-run" class="sa-btn">开始体检</button>
    </div>
    <div id="sa-msg" class="sa-msg"></div>
  </div>

  <div id="sa-result" class="sa-result" hidden>
    <div class="sa-top">
      <div class="sa-gauge">
        <div class="sa-score" id="sa-score">0</div>
        <div class="sa-score-lbl">综合得分</div>
      </div>
      <div class="sa-counts">
        <div class="sa-count"><span class="sa-dot err"></span><b id="sa-c-err">0</b> 严重问题</div>
        <div class="sa-count"><span class="sa-dot warn"></span><b id="sa-c-warn">0</b> 建议优化</div>
        <div class="sa-count"><span class="sa-dot pass"></span><b id="sa-c-pass">0</b> 通过</div>
        <div class="sa-url-shown" id="sa-url-shown"></div>
      </div>
    </div>

    <div id="sa-groups"></div>

    <div class="sa-funnel">
      <div>📌 内容类问题（标题/描述/正文太薄/可引用性低）——用 <b>关键词库 → 标题库 → 内容引擎</b> 一键补齐，再来体检看分数提升。</div>
    </div>
  </div>
</div>

<style>
  .sa{max-width:980px}
  .sa-head h1{font-size:22px;font-weight:600;color:#141413;margin:0}
  .sa-pill{display:inline-block;vertical-align:middle;font-size:11px;font-weight:600;color:#1B365D;background:#eaf0f8;border-radius:999px;padding:2px 9px;margin-left:8px}
  .sa-sub{color:#5e5d59;font-size:13px;margin:6px 0 18px}
  .sa-card{background:#fff;border:1px solid #e8e5da;border-radius:14px;padding:16px}
  .sa-form{display:flex;gap:10px;flex-wrap:wrap}
  .sa-form input{flex:1;min-width:260px;height:42px;padding:0 14px;border:1px solid #d8d4c8;border-radius:10px;font-size:14px;outline:none}
  .sa-form input:focus{border-color:#1B365D}
  .sa-btn{height:42px;padding:0 22px;background:#1B365D;color:#fff;border:0;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap}
  .sa-btn:hover{background:#16294a}
  .sa-btn:disabled{opacity:.6;cursor:default}
  .sa-msg{font-size:12.5px;margin-top:10px;display:none}
  .sa-msg.show{display:block}
  .sa-msg.error{color:#c0392b}
  .sa-result{margin-top:18px}
  .sa-top{display:flex;gap:24px;align-items:center;background:#fff;border:1px solid #e8e5da;border-radius:14px;padding:18px 22px;margin-bottom:14px}
  .sa-gauge{text-align:center;min-width:110px}
  .sa-score{font-size:44px;font-weight:700;line-height:1}
  .sa-score-lbl{font-size:12px;color:#8a877f;margin-top:4px}
  .sa-counts{display:flex;gap:22px;align-items:center;flex-wrap:wrap}
  .sa-count{font-size:13px;color:#3d3d3a}
  .sa-count b{font-size:16px;margin:0 3px}
  .sa-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:5px;vertical-align:middle}
  .sa-dot.err{background:#e0573e}.sa-dot.warn{background:#d9a534}.sa-dot.pass{background:#3a9d6e}
  .sa-url-shown{font-size:12px;color:#8a877f;margin-left:auto}
  .sa-group{background:#fff;border:1px solid #e8e5da;border-radius:14px;padding:6px 0;margin-bottom:14px}
  .sa-group-h{font-size:13px;font-weight:600;color:#1B365D;padding:10px 20px 8px;border-bottom:1px solid #f0ede4}
  .sa-item{display:flex;gap:12px;padding:13px 20px;border-bottom:1px solid #f5f3ec}
  .sa-item:last-child{border-bottom:0}
  .sa-badge{flex-shrink:0;width:54px;height:22px;border-radius:6px;font-size:11px;font-weight:600;display:flex;align-items:center;justify-content:center}
  .sa-badge.err{background:#fcebe7;color:#c0392b}
  .sa-badge.warn{background:#fbf2dd;color:#9a7415}
  .sa-badge.pass{background:#e7f4ee;color:#2c7a55}
  .sa-it-body{flex:1;min-width:0}
  .sa-it-label{font-size:13.5px;font-weight:600;color:#262522}
  .sa-it-detail{font-size:12.5px;color:#5e5d59;margin-top:2px}
  .sa-it-fix{font-size:12.5px;color:#8a877f;margin-top:3px}
  .sa-it-fix:before{content:"建议：";color:#b0ada3}
  .sa-funnel{background:#f7f4ec;border:1px dashed #d8cfb8;border-radius:12px;padding:14px 18px;font-size:13px;color:#6b5d3a;margin-top:4px}
</style>

@push('scripts')
<script>
(function(){
  var RUN_URL = "{{ route('admin.site-audit.run') }}";
  var CSRF = document.querySelector('meta[name=csrf-token]').content;
  var $url = document.getElementById('sa-url');
  var $btn = document.getElementById('sa-run');
  var $msg = document.getElementById('sa-msg');
  var $res = document.getElementById('sa-result');

  function msg(text, isErr){ $msg.textContent = text; $msg.className = 'sa-msg show' + (isErr ? ' error' : ''); }
  function clearMsg(){ $msg.className = 'sa-msg'; }

  var BADGE = { pass: ['pass','通过'], warn: ['warn','建议'], error: ['err','严重'] };

  function render(r){
    document.getElementById('sa-score').textContent = r.score;
    document.getElementById('sa-score').style.color = r.score >= 80 ? '#3a9d6e' : (r.score >= 55 ? '#d9a534' : '#e0573e');
    document.getElementById('sa-c-err').textContent = r.summary.error || 0;
    document.getElementById('sa-c-warn').textContent = r.summary.warn || 0;
    document.getElementById('sa-c-pass').textContent = r.summary.pass || 0;
    document.getElementById('sa-url-shown').textContent = r.url;

    var html = '';
    r.groups.forEach(function(g){
      html += '<div class="sa-group"><div class="sa-group-h">' + g.label + '</div>';
      g.items.forEach(function(it){
        var b = BADGE[it.status] || BADGE.warn;
        html += '<div class="sa-item">'
          + '<span class="sa-badge ' + b[0] + '">' + b[1] + '</span>'
          + '<div class="sa-it-body"><div class="sa-it-label">' + esc(it.label) + '</div>'
          + '<div class="sa-it-detail">' + esc(it.detail) + '</div>'
          + '<div class="sa-it-fix">' + esc(it.fix) + '</div></div></div>';
      });
      html += '</div>';
    });
    document.getElementById('sa-groups').innerHTML = html;
    $res.hidden = false;
  }

  function esc(s){ var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

  function run(){
    var url = $url.value.trim();
    if(!url){ msg('请输入网址', true); return; }
    $btn.disabled = true; $btn.textContent = '体检中…'; clearMsg(); $res.hidden = true;
    fetch(RUN_URL, {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
      body: JSON.stringify({ url: url })
    }).then(function(resp){ return resp.json().then(function(j){ return {ok: resp.ok, j: j}; }); })
      .then(function(o){
        if(!o.ok || o.j.ok === false){ msg(o.j.error || '体检失败', true); return; }
        render(o.j);
      })
      .catch(function(e){ msg('请求出错：' + e.message, true); })
      .finally(function(){ $btn.disabled = false; $btn.textContent = '开始体检'; });
  }

  $btn.addEventListener('click', run);
  $url.addEventListener('keydown', function(e){ if(e.key === 'Enter') run(); });
})();
</script>
@endpush
@endsection
