# GEOFlow SaaS Addon(Beacon 换肤 + 多行业 GEO 引擎)

在开源 [GEOFlow](https://github.com/yaojingang/GEOFlow) 之上的**纯新增/覆盖**插件层:
- **Beacon 后台换肤**:navy 侧栏 + 顶栏 + 简单/专家切换 + 全局 Beacon 配色字体(`public/` + `resources/views/admin/`)。
- **多行业问题集群引擎**:核心词 → 问题集群/GEO标题,行业包(travel/medical)为数据,带校验+自动修复(`app/Services/GeoEngine/`、`config/`、`resources/views/geoengine/`)。

> 功能零改动:复用 GEOFlow 原有路由/菜单/翻译/逻辑,仅换展示 + 新增引擎页。

## 文件作用
| 路径 | 操作 |
|------|------|
| `public/geoui-beacon.css` | 新增 · Beacon 主题样式 |
| `resources/views/admin/layouts/app.blade.php` | 覆盖 · 改侧栏+主区结构 |
| `resources/views/admin/partials/header.blade.php` | 覆盖 · Beacon 侧栏导航 |
| `resources/views/admin/partials/topbar.blade.php` | 新增 · 顶栏+模式切换 |
| `app/Services/GeoEngine/Engine.php` | 新增 · 引擎 |
| `app/Http/Controllers/GeoEngineController.php` | 新增 · 引擎页控制器 |
| `config/geoengine.php` `config/geo_packs.php` | 新增 · 引擎配置 + 行业包 |
| `resources/views/geoengine/generate.blade.php` | 新增 · 引擎页 |

## 部署(Linux 服务器)

```bash
# 1. 拉 GEOFlow
cd /opt && git clone https://github.com/yaojingang/GEOFlow.git && cd GEOFlow
cp .env.example .env

# 2. 叠加本插件(把本仓库内容覆盖进 GEOFlow)
cd /opt && git clone <你的本仓库地址> geoflow-saas-addon
cp -r geoflow-saas-addon/public geoflow-saas-addon/resources geoflow-saas-addon/app geoflow-saas-addon/config /opt/GEOFlow/

# 3. 配 .env：APP_URL、PGVECTOR_IMAGE=pgvector/pgvector:pg16、DEEPSEEK_API_KEY、改 admin 密码
cd /opt/GEOFlow
sed -i 's#^APP_URL=.*#APP_URL=http://你的IP:18080#' .env
sed -i 's#^PGVECTOR_IMAGE=.*#PGVECTOR_IMAGE=pgvector/pgvector:pg16#' .env
sed -i 's#^DEEPSEEK_API_KEY=.*#DEEPSEEK_API_KEY="你的key"#' .env

# 4. 加引擎路由（routes/web.php 顶部）
#   use App\Http\Controllers\GeoEngineController;
#   Route::get('/geo-engine', [GeoEngineController::class, 'index']);
#   Route::post('/geo-engine/generate', [GeoEngineController::class, 'generate']);

# 5. 编译前端 + 起容器
npm install && npm run build
docker compose build && docker compose up -d
docker compose exec app php artisan optimize:clear
```

访问:后台 `http://你的IP:18080/geo_admin` · 引擎 `http://你的IP:18080/geo-engine`

许可:基于 GEOFlow(Apache-2.0)。
