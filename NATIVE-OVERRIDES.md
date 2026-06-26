# 原生文件覆盖清单（GEO SaaS 二开）

本仓库以"叠加(cp)"方式覆盖 GEOFlow。**绝大多数是新增文件**（不影响升级）。
下表是**少数覆盖了 GEOFlow 原生文件**的清单——每次升级 GEOFlow 上游后，需对照上游新版本重新合并这些改动。

> 原则：能不改原生就不改；要改也只改最小面、并把"逻辑/策略"外置到我们自己的 config（行业包），原生文件里只留"读 config"的薄改动。

| 覆盖的原生文件 | 改了什么 | 为什么没有别的办法 | 升级合并提示 |
|---|---|---|---|
| `app/Services/GeoFlow/TitleAiGenerationService.php` | 标题生成 prompt 从写死 → 读 `config('geo_packs.{pack}.prompts.title_generation')`；`pack`/`subject` 优先入参、其次 `request()` 兜底；**未配行业包时与原生完全一致** | prompt 写死在服务内，要"多行业不同 prompt"必须在此读取行业包 | 看上游是否改了 `requestTitlesFromModel`，把"行业包分支"重新贴回 |
| `resources/views/admin/title-libraries/ai-generate.blade.php` | 表单增加「行业包 + 主体」两个字段（行业列表读 `config('geo_packs')`，主体默认站点名）；其余原样 | 需要让用户在原生"AI 生成标题"页选择行业与主体 | 看上游是否改了表单结构，把这两个字段重新插入 grid |
| `resources/views/admin/keyword-libraries/detail.blade.php` | 头部操作区增加一个「AI 扩词」按钮，链到 `admin.keyword-libraries.ai-expand`；其余原样 | 关键词库需要一个"主动产词"入口（原生只有 URL 被动产词） | 看上游是否改了头部按钮区，把「AI 扩词」`<a>` 重新插入 |
| `resources/views/admin/keyword-libraries/form.blade.php` | 创建模式下在表单上方增加「用 AI 生成并创建」卡片（建库+填词一步，POST 到 `admin.keyword-libraries.ai-new`）；编辑模式与原生一致 | 创建库时就能 AI 生成内容，免去"先建空库再填" | 看上游是否改了 form，把 `@if(!$isEdit)` 的 AI 卡片重新贴回 |
| `resources/views/admin/materials/index.blade.php` | 把「关键词库管理」「标题库管理」两张卡片 `href` 改为 `admin.keyword-workbench.index` / `admin.title-workbench.index`（指向自有工作台）；其余原样（2 行改动） | 统一关键词/标题入口到自有功能模块,原生那套退为内部管道 | 看上游是否改了卡片数组,把这 2 行 href 重新改过来 |

## 没有改的（用更稳的方式接入）
- **路由/鉴权**：用 `app/Providers/GeoSaasServiceProvider.php` 注入，不改 `routes/web.php`。
- **控制器**：标题生成的 `pack`/`subject` 经 `request()` 兜底读取，**未改** `TitleLibraryController`。
- **侧栏**：`resources/views/admin/partials/header.blade.php` 是我们整体重皮的版本（Beacon），已属我们维护范畴。

## 行业策略的唯一来源
所有"行业不同的 prompt / 词表 / 阈值 / 合规"集中在 `config/geo_packs.php`。
加行业 = 加一个 slug；调 prompt = 改这个文件——**不碰原生代码**。
