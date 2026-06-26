<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\GeoSaasServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    // GEO SaaS addon：注入自有后台路由（routes/geo_saas.php），不改原生 routes/web.php。
    // 注意：本文件覆盖原生 bootstrap/providers.php。升级 GEOFlow 时若上游新增了 Provider，
    //       需把上面两行对照上游补齐（见 NATIVE-OVERRIDES.md）。
    GeoSaasServiceProvider::class,
];
