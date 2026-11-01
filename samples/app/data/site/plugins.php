<?php
// Module content, loaded only for the pages that use it.
return [
            ['name' => 'Tpl', 'group' => '基础', 'type' => 'base', 'text' => '模板编译与变量赋值，支持条件、循环和公共模板拆分。本站正在使用。', 'need' => '需要可信模板与可写编译目录'],
            ['name' => 'Config', 'group' => '基础', 'type' => 'base', 'text' => '解析分组配置，支持数字、布尔值及重复键，并提供缓存。', 'need' => '配置文件；开发时可关闭缓存'],
            ['name' => 'Json', 'group' => '基础', 'type' => 'base', 'text' => 'JSON 编解码与错误查询，支持原生 flags 和 depth 参数。', 'need' => '应用自行设置响应头与错误策略'],
            ['name' => 'Route', 'group' => '基础', 'type' => 'base', 'text' => '使用正则规则匹配请求路径，映射为核心调度路径。', 'need' => '在 run() 前注册规则'],
            ['name' => 'Log', 'group' => '基础', 'type' => 'base', 'text' => '提供分级日志，可配置控制台输出与文件记录。', 'need' => '写文件时需要目录权限'],
            ['name' => 'Db', 'group' => '数据', 'type' => 'data', 'text' => '提供数据库表操作及读写配置组织能力。', 'need' => '数据库服务、对应驱动和连接配置'],
            ['name' => 'Redis', 'group' => '数据', 'type' => 'data', 'text' => '封装 Redis 的配置与访问，适合缓存等数据场景。', 'need' => 'Redis PHP 扩展及实际服务'],
            ['name' => 'Http', 'group' => '扩展', 'type' => 'extra', 'text' => '通过 Guzzle 发起 HTTP 请求，支持超时、请求头与参数。', 'need' => 'Composer 安装 Guzzle 与可用网络'],
            ['name' => 'Language', 'group' => '扩展', 'type' => 'extra', 'text' => '组织多语言词条与语言文件。', 'need' => '准备 locale 资源'],
            ['name' => 'Captcha', 'group' => '扩展', 'type' => 'extra', 'text' => '基于 Gregwar Captcha 生成验证码图片。', 'need' => 'Composer 依赖、GD、mbstring、fileinfo 和会话'],
            ['name' => 'Error', 'group' => '扩展', 'type' => 'extra', 'text' => '格式化并记录 PHP 错误和异常。', 'need' => '需配置适合运行环境的错误输出策略'],
        ];
