# Language 使用手册与示例

运行示例：`/lab/language/entry`。从官网插件页 Language 卡片点击“运行示例”，选择简体中文、繁體中文或 English，输入称呼后提交。页面显示实际翻译、当前 locale、语言文件内容、接入代码，以及缺失词条的回退结果。普通 GET 表单无需 JavaScript。

源码：[LanguageController.php](../samples/app/Lab/LanguageController.php)、[language.tpl](../samples/app/templates/workspace/language.tpl)、[中文示例词条](../samples/app/languages/zh-cn/demo.json)。

## 语言目录和词条

引入 Composer 自动加载器后即可使用，不需要额外 Composer 包。按 `locale/zone.json` 或 `locale/zone.jsonc` 组织语言目录：

```text
app/languages/
  zh-cn/demo.json
  zh-tw/demo.json
  en/demo.json
```

例如 `zh-cn/demo.json`：

```json
{
    "greeting": "你好，{name}！欢迎体验 SlightPHP 多语言。",
    "action": "开始使用"
}
```

文件必须是 JSON 对象，每个词条的值都必须是字符串，不支持嵌套对象或数组。同一 locale / zone 同时存在 JSON 与 JSONC 时优先 JSON；JSONC 支持注释，解析行为见 [Config 手册](config.md)。

## 设置语言并翻译

```php
use SlightPHP\Language;

Language::setLanguageDir(SlightPHP::getAppDir() . '/languages');
Language::setLocale('zh-cn');

$message = strtr(Language::tr('greeting', 'demo'), ['{name}' => '小明']);
$action = Language::tr('action', 'demo');
$missing = Language::tr('missing_demo_key', 'demo');
```

第二个参数 zone 对应文件名，不带扩展名；省略时读取 main。占位符替换由应用通过 strtr 等方法完成，插件不自动替换 `{name}`。缺失词条或缺少候选语言文件时返回原文，因此 missing 的值为 `missing_demo_key`。

Language 返回未编码的字符串。在原生 PHP 输出 HTML 时使用 htmlspecialchars；传入 Tpl 时保留原始数据，由模板按实际上下文自动转义，不对用户输入使用 raw：

```php
$tpl = new \SlightPHP\Tpl();
$tpl->render('greeting.tpl', ['message' => $message]);
```

```html
<p>{$message}</p>
```

## 语言选择与请求状态

| API / 属性 | 行为 |
| --- | --- |
| setLanguageDir($dir)、getLanguageDir() | 设置或读取语言根目录；设置时清空 Language 的已加载词条缓存 |
| setLocale($locale)、getLocale() | 设置或读取强制语言；设置时清空候选语言列表，保留已加载词条 |
| tr($source, $zone = 'main') | 查找字符串词条，找不到时返回 source |
| resetRequest() | 清除强制语言和候选列表，下次翻译时重新读取请求语言 |
| $defaultLocale | 请求候选列表包含此语言时，将其作为唯一候选；不是任意缺失词条的固定回退语言 |

设置了非空 locale 时只查找该语言，不会自动追加英文或中文。未强制语言时按 Accept-Language 中的出现顺序读取，并为地区标签追加基础语言候选；忽略 q=0，不按 q 数值重新排序。

samples 在入口通过 [I18n.php](../samples/app/I18n.php) 先选择受支持的三种语言，再调用 setLocale。该站点的优先级是有效 lang 查询参数、语言 Cookie、Accept-Language，最后简体中文；地区标签映射、Cookie 保存及默认中文由 samples 实现，不能当作 Language 类本身的规则。

常驻服务应在每个请求开始时调用 resetRequest，再明确设置本次语言。它保留语言目录、defaultLocale、词条缓存及 Config 缓存，不提供并发请求间的静态状态隔离。修改词条后，还需考虑 Config 的进程缓存；开发时可在首次读取前设置 `Config::$CACHE = false`，生产常驻进程由应用安排重新加载。

## 错误与验证

JSON / JSONC 语法错误抛 JsonException，文件不是对象或词条不是字符串抛 InvalidArgumentException，文件无法读取抛 RuntimeException。缺失文件、词条以及不合法的 locale / zone 通常返回原文。应用选择的语言与 zone 应来自受控列表，不接受任意文件路径。

依次选择三种语言并输入 Alice，可观察 greeting 与 action 随语言变化，页面同时展示对应的 demo.json。缺失键始终显示 missing_demo_key。输入包含 `<`、`&` 或引号的称呼时只显示文字，不执行 HTML；空称呼、数组参数或超过 80 个字符的输入返回 400，并显示输入提示。

更多交互入口见[插件指南](plugins.md)与[完整示例](samples.md)。
