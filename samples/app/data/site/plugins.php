<?php
// Module content, loaded only for the pages that use it.
return [
            ['name' => 'STpl', 'group' => '基础', 'type' => 'base', 'text' => '模板编译与变量赋值，支持条件、循环和公共模板拆分。本站正在使用。', 'need' => '需要可信模板与可写编译目录'],
            ['name' => 'SConfig', 'group' => '基础', 'type' => 'base', 'text' => '解析分组配置，支持数字、布尔值及重复键，并提供缓存。', 'need' => '配置文件；开发时可关闭缓存'],
            ['name' => 'SJson', 'group' => '基础', 'type' => 'base', 'text' => 'JSON 编解码与错误查询，支持原生 flags 和 depth 参数。', 'need' => '应用自行设置响应头与错误策略'],
            ['name' => 'SRoute', 'group' => '基础', 'type' => 'base', 'text' => '使用正则规则匹配请求路径，映射为核心调度路径。', 'need' => '在 run() 前注册规则'],
            ['name' => 'SLog', 'group' => '基础', 'type' => 'base', 'text' => '提供分级日志，可配置控制台输出与文件记录。', 'need' => '写文件时需要目录权限'],
            ['name' => 'SDb', 'group' => '数据', 'type' => 'data', 'text' => '提供数据库表操作及读写配置组织能力。', 'need' => '数据库服务、对应驱动和连接配置'],
            ['name' => 'SRedis', 'group' => '数据', 'type' => 'data', 'text' => '封装 Redis 的配置与访问，适合缓存等数据场景。', 'need' => 'Redis PHP 扩展及实际服务'],
            ['name' => 'SCache', 'group' => '数据', 'type' => 'data', 'text' => '提供 Memcache、文件或 APC 等历史缓存后端。', 'need' => '按后端检查扩展、权限与版本兼容性'],
            ['name' => 'SHttp', 'group' => '扩展', 'type' => 'extra', 'text' => '通过 Guzzle 发起 HTTP 请求，支持超时、请求头与参数。', 'need' => 'Composer 安装 Guzzle 与可用网络'],
            ['name' => 'SLanguage', 'group' => '扩展', 'type' => 'extra', 'text' => '组织多语言词条与语言文件。', 'need' => '准备 locale 资源'],
            ['name' => 'SCaptcha / SThumbnail', 'group' => '扩展', 'type' => 'extra', 'text' => '验证码生成与图片缩略图处理。', 'need' => 'GD、字体或图片资源'],
            ['name' => 'SError / SMail / SSphinx', 'group' => '扩展', 'type' => 'extra', 'text' => '错误、邮件与搜索等历史插件，可根据具体业务参考。', 'need' => '需逐项检查服务依赖和当前 PHP 兼容性'],
        ];
