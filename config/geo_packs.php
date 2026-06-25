<?php
// 行业包（数据层）。放置：项目根 /config/geo_packs.php
// 加新行业 = 在此数组再加一个 slug 键，引擎代码不用改。
return [

    // ===================== 旅游 =====================
    'travel' => [
        'slug' => 'travel',
        'name' => '旅游',
        'version' => '1.0.0',
        'stages' => [
            ['key' => '知晓期', 'order' => 1, 'intent_bias' => ['KNOWLEDGE', 'WEAK']],
            ['key' => '熟悉期', 'order' => 2, 'intent_bias' => ['WEAK', 'MEDIUM']],
            ['key' => '考虑期', 'order' => 3, 'intent_bias' => ['MEDIUM', 'STRONG']],
            ['key' => '购买期', 'order' => 4, 'intent_bias' => ['STRONG']],
        ],
        'taxonomy' => [
            'types'   => ['BRAND', 'PRODUCT', 'BUSINESS', 'FAQ'],
            'intents' => ['KNOWLEDGE', 'WEAK', 'MEDIUM', 'STRONG'],
        ],
        'routing_rules' => [
            ['type' => 'visa',        'match' => '签证|面签|拒签|材料|visa'],
            ['type' => 'customTour',  'match' => '定制游|定制旅行|私人团|包团|包车|小团'],
            ['type' => 'tourProduct', 'match' => '旅游团|跟团游|当地参团|报团|当地团|参团'],
            ['type' => 'generalTravel', 'match' => '*'],
        ],
        'strategies' => [
            'generalTravel' => "【泛旅游词策略】围绕目的地认知与决策：景点、季节、天数、路线、预算、自由行/跟团、当地参团、亲子、带父母、行程节奏、费用、报名前关注点。知晓期偏基础认知，熟悉期偏出行方式与人群，考虑期偏费用与团型比较，购买期偏报名/班期/余位/退改/材料。",
            'tourProduct'   => "【旅游团策略】至少 60% 的 merchantVersion 必须含旅游团/跟团游/报团/当地参团/团费/班期/余位/报名/退改/团型/自费/购物等业务语义。购买期高度贴合报名、班期、余位、付款、材料、退改、团费包含。",
            'visa'          => "【签证策略】围绕是否需签证、材料、办理时间、面签、拒签、签证未过团费如何处理、报名与签证顺序、亲子/老人材料。严禁承诺包过、一定出签、零风险；涉及结果只能用疑问形式。",
            'customTour'    => "【定制游策略】围绕适合人群、私人团与跟团区别、包车包团、行程自由度、预算、亲子/老人/企业团建、小团定制、咨询与确认流程。严禁最低价、服务承诺结论、宣传标题。",
        ],
        'lexicon' => [
            'third_party'   => ['旅行社', '平台', '商家', '机构', '供应商'],
            'forbidden'     => ['保证', '包过', '零风险', '必选', '全退', '最低价', '产品列表', '线路列表', '产品详情', '退改政策', '退款政策', '售后政策', '合同条款', '规则说明'],
            'ending_words'  => ['查询', '流程', '政策', '对比', '说明', '指南', '详解', '分析', '攻略', '列表', '详情', '报告'],
            'question_words' => ['吗', '什么', '怎么', '哪些', '哪个', '多少', '会不会', '能不能', '有没有', '合适', '区别'],
            'decision_vars' => ['行程怎么安排', '适合几天', '适合人群', '费用差多少', '预算大概多少', '自费项目多不多', '有没有购物安排', '团费包含什么', '班期', '余位', '退改'],
        ],
        'thresholds' => [
            'merchant_len' => [16, 45],
            'min_commercial_ratio' => 0.6,
            'max_knowledge' => 2,
            'purchase_strong_ratio' => 0.7,
            'repair_max_retry' => 2,
        ],
        'compliance' => [
            'risk_level' => 'low',
            'banned_claims' => ['保证', '包过', '零风险', '一定出签', '最低价', '百分百'],
            'required_disclaimers' => [],
        ],
    ],

    // ===================== 医疗健康（差异最大，用于压测抽象 + 合规高危） =====================
    'medical' => [
        'slug' => 'medical',
        'name' => '医疗健康',
        'version' => '1.0.0',
        'stages' => [
            ['key' => '症状认知', 'order' => 1, 'intent_bias' => ['KNOWLEDGE', 'WEAK']],
            ['key' => '了解疾病', 'order' => 2, 'intent_bias' => ['WEAK', 'MEDIUM']],
            ['key' => '对比就医', 'order' => 3, 'intent_bias' => ['MEDIUM', 'STRONG']],
            ['key' => '就诊决策', 'order' => 4, 'intent_bias' => ['STRONG']],
            ['key' => '复诊随访', 'order' => 5, 'intent_bias' => ['MEDIUM', 'STRONG']],
        ],
        'taxonomy' => [
            'types'   => ['BRAND', 'PRODUCT', 'BUSINESS', 'FAQ'],
            'intents' => ['KNOWLEDGE', 'WEAK', 'MEDIUM', 'STRONG'],
        ],
        'routing_rules' => [
            ['type' => 'default', 'match' => '*'],
        ],
        'strategies' => [
            'default' => "【医疗健康策略】围绕症状、可能病因、检查项目、就诊科室、挂号、费用与医保、治疗方式选择、医生与机构对比、术后/复诊随访。严禁医疗效果承诺与诊断结论。症状认知偏科普疑问，了解疾病偏检查与科室，对比就医偏科室/费用/医保/医生选择，就诊决策偏挂号/预约/材料/流程，复诊随访偏复查/康复/注意事项。所有内容只提供“用户会问的问题”，不给医疗建议或确定结论。",
        ],
        'lexicon' => [
            'third_party'   => ['医院', '诊所', '平台', '机构'],
            'forbidden'     => ['最好的', '排名第一', '权威认证', '科普指南', '诊疗规范', '政策解读', '费用清单', '详解', '攻略'],
            'ending_words'  => ['查询', '流程', '政策', '说明', '指南', '详解', '分析', '攻略', '列表', '详情', '规范'],
            'question_words' => ['吗', '什么', '怎么', '哪些', '哪个', '多少', '会不会', '能不能', '有没有', '需要', '区别'],
            'decision_vars' => ['挂哪个科', '要做哪些检查', '费用大概多少', '医保能报吗', '多久能恢复', '需要准备什么', '术后注意什么'],
        ],
        'thresholds' => [
            'merchant_len' => [14, 42],
            'min_commercial_ratio' => 0.55,
            'max_knowledge' => 3,
            'purchase_strong_ratio' => 0.6,
            'repair_max_retry' => 2,
        ],
        'compliance' => [
            'risk_level' => 'high',
            'banned_claims' => ['治愈', '根治', '包好', '保证', '百分百', '无副作用', '最好的', '特效', '药到病除', '永不复发'],
            'required_disclaimers' => ['内容仅为常见疑问整理，不构成诊疗建议，请以医生面诊为准'],
        ],
    ],

];
