#!/bin/bash
# GEOFlow 更新部署脚本（合并模式，不会覆盖你在 geo-saas 上的二次开发）
#
# 流程：先拉你仓库里的最新提交 -> 合并上游更新 -> 装依赖/构建/迁移 -> 推回你的仓库
# 如果合并出现冲突，脚本会在这里停下，绝不会强推覆盖，需要你或我手动处理冲突后再继续。
set -e
cd /opt/geoflow-app

echo "==> 拉取你自己仓库(origin/geo-saas)上的最新提交（含你的二次开发）"
git fetch origin
git merge --ff-only origin/main || {
    echo "!! origin/main 有你在别处直接提交但服务器还没有的改动，且无法快进合并。"
    echo "!! 请先手动执行 git pull origin main 处理，脚本已停止，不会继续。"
    exit 1
}

echo "==> 拉取官方上游(upstream/GEOFlow)最新更新"
git fetch upstream

echo "==> 合并上游更新到当前分支"
if ! git merge upstream/main -m "Merge upstream GEOFlow updates"; then
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
    echo "!! 合并冲突：你的二次开发和上游更新改了同一处代码，需要人工确认。"
    echo "!! 脚本已停止，没有推送任何东西，你的代码和仓库都是安全的。"
    echo "!! 冲突文件："
    git diff --name-only --diff-filter=U
    echo "!! 处理完冲突后，运行: git add -A && git commit --no-edit"
    echo "!! 然后重新运行这个脚本，会跳过已完成的合并步骤继续往下走。"
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
    exit 1
fi

echo "==> 安装/更新 PHP 依赖"
composer install --no-interaction --prefer-dist --no-dev

echo "==> 安装/更新前端依赖并构建"
npm install
npm run build

echo "==> 跑数据库迁移（如果有新表/新字段）"
php artisan migrate --force

echo "==> 清理并重建缓存"
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
php artisan config:cache

echo "==> 修复权限"
chown -R www-data:www-data storage bootstrap/cache public/build

echo "==> 重启后台常驻服务"
systemctl restart geoflow-queue geoflow-scheduler geoflow-reverb
systemctl reload php8.4-fpm

echo "==> 推回你自己的仓库（普通推送，不是强推，历史保留完整）"
git push origin main

echo "==> 检查服务状态"
systemctl is-active nginx php8.4-fpm geoflow-queue geoflow-scheduler geoflow-reverb

echo "==> 全部完成！"
