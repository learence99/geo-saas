#!/usr/bin/env bash
#
# 白标处理（部署期执行）。把已部署的 GEOFlow 里"用户可见"的品牌/作者痕迹换成自有品牌。
#
#   用法:  bash whitelabel.sh [GEOFLOW根目录] [品牌名]
#   默认:  bash whitelabel.sh /opt/GEOFlow "GEO SAAS"
#
# 重要：每次用 cp 覆盖原生文件后，原生文件会被重新拷成"未处理"状态，所以本脚本要在
#       每次部署的 cp 之后、optimize:clear 之前重跑一次。脚本是幂等的，可反复执行。
#
# 处理范围：只动 lang（文案大头）与 resources/views/admin（可见后台页面）。
#   不碰 config / 代码 / 命名空间 / 主题 / 前台 / 数据库（这些里的 geoflow 是功能标识，动了会坏）。
#
# 安全保护（为什么不会误伤）：
#   · 命名空间 \GeoFlow         → 正则用 (?<!\\) 负向断言跳过
#   · 环境变量 GEOFLOW_XXX      → 全大写，"GeoFlow"/"GEOFlow" 两个模式都命中不到它
#   · 配置键/资源路径 geoflow.  → 全小写，同样命中不到
#
set -euo pipefail
ROOT="${1:-/opt/GEOFlow}"
BRAND="${2:-GEO SAAS}"
export WL_BRAND="$BRAND"

if [ ! -d "$ROOT/resources/views/admin" ]; then
  echo "✗ 找不到 $ROOT/resources/views/admin —— 路径不对？" >&2
  exit 1
fi

# ── ① 移除 dashboard 的"技能资源"作者推广 section（3 个 github.com/yaojingang 外链卡片）
DASH="$ROOT/resources/views/admin/dashboard.blade.php"
if [ -f "$DASH" ]; then
  perl -0777 -i -pe "s{<section>\s*<div class=\"mb-5\">\s*<h2[^>]*>\{\{ __\('admin\.dashboard\.skill_resources\.title'\).*?</section>}{}s" "$DASH"
fi

# ── ②③ 在 lang 与 admin 视图里替换品牌、署名、作者外链
COUNT=0
while IFS= read -r -d '' f; do
  perl -i -pe '
    BEGIN { our $b = $ENV{"WL_BRAND"}; }
    # ③ 作者外链先处理（否则品牌替换会把 URL 里的 GEOFlow 也换掉，留下半截）
    #    注意：用受限字符集匹配 URL 路径，遇到引号/逗号/空格/尖括号即停，
    #    否则 \S* 会贪婪吃掉闭合引号和逗号，导致 PHP 语法错误。
    s{https?://(?:x\.com|twitter\.com)/yaojingang[A-Za-z0-9._/\-]*}{#}g;
    s{https?://github\.com/yaojingang/[A-Za-z0-9._/\-]*}{#}g;
    s/姚金刚/$b/g;
    # ② 品牌：GeoFlow（保护命名空间）、GEOFlow → 品牌名
    s/(?<!\\)GeoFlow/$b/g;
    s/GEOFlow/$b/g;
  ' "$f"
  COUNT=$((COUNT+1))
done < <(find "$ROOT/lang" "$ROOT/resources/views/admin" -type f \( -name '*.php' -o -name '*.blade.php' \) -print0)

# ── ④ 企业知识库（v2.1.0）AI 系统提示词里的品牌。精确字符串替换，不碰命名空间/类名。
EKS="$ROOT/app/Services/GeoFlow/EnterpriseKnowledgeDraftService.php"
if [ -f "$EKS" ]; then
  perl -i -pe 'BEGIN{$b=$ENV{"WL_BRAND"}} s/你是 GEOFlow 企业知识库整理助手/你是 $b 企业知识库整理助手/g' "$EKS"
fi

echo "✓ 白标完成  根目录=$ROOT  品牌=$BRAND  处理文件=$COUNT"
echo "  记得接着执行： docker compose exec app php artisan optimize:clear"
