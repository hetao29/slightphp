<?php
namespace SlightPHP\Tpl;

/**
 * 自动模式一个实际渲染流的 HTML 词法状态及输出缓冲。
 *
 * literal 处理可信源码/raw/片段，value 按当前状态编码数据；状态随实际执行路径更新，
 * 因而能跨 if、循环及同实例 include 保持连续。Tpl 只在最外层创建和结束本对象。
 *
 * text 可直接输出；tag/comment/body 暂存于 pending。标签在结束时验证动态 URL/style
 * 组合值；style 内容在完整结束时校验，script/style 等正文也要等结束标签后才输出。
 * 出错不回滚之前的输出，也不替应用捕获异常或清理业务副作用。
 *
 * 这是面向所支持插值位置的词法模型，不是 DOM 树、HTML 清洗器或完整浏览器解析器。
 * 自动值不支持动态标签名、属性名、注释及 SVG/MathML；可信原文和 raw 仍由调用方负责。
 *
 * @internal 由 Tpl 和 Compiler 管理，不能在不同请求或不同不连续输出流间复用。
 */
final class Context
{
    /** @var JsPlanCache 编译阶段收集/运行阶段共享的 JS 状态计划。 */
    private JsPlanCache $javascriptPlanCache;
    /** @var JsContext|null 当前 script 的计划执行流，不与事件属性共用。 */
    private ?JsContext $javascriptContext = null;
    /** @var JsContext|null 当前事件属性的计划执行流。 */
    private ?JsContext $attributeJavascriptContext = null;
    /** @var string 上次事件插值之后的静态 HTML 属性源码，解码后交给计划执行流。 */
    private string $attributeJavascriptLiteral = '';
    /** @var int 实际输出流的修订号，用于拒绝转换回调中途改变已经选定的输出位置。 */
    private int $revision = 0;

    /**
     * 默认用于编译分析；实际渲染由 Tpl 传入带持久上下文缓存的计划集。
     * @param JsPlanCache|null $javascriptPlanCache 共享的 JS 计划集；null 时创建仅用于当前流的计划集。
     */
    public function __construct(?JsPlanCache $javascriptPlanCache = null)
    {
        $this->javascriptPlanCache = $javascriptPlanCache ?? new JsPlanCache();
    }

    /**
     * 编译期分支共享计划表，但每条路径独立推进元素/属性的执行状态。
     * @return void
     */
    public function __clone()
    {
        if ($this->javascriptContext !== null) $this->javascriptContext = clone $this->javascriptContext;
        if ($this->attributeJavascriptContext !== null) $this->attributeJavascriptContext = clone $this->attributeJavascriptContext;
    }

    /**
     * 子模板的编译文件向共享渲染流注册它自身预先分析的 JS 计划。
     * @param array<string,array<string,mixed>> $plans 以状态和源码摘要为键的 JS 计划表。
     * @return void
     */
    public function registerJavascriptPlans(array $plans): void { $this->javascriptPlanCache->register($plans); }

    /** @var string 当前词法状态：text、tag、comment 或 body；不记录普通 DOM 元素栈。 */
    private string $state = 'text';
    /** @var string 当前未发出的标签、注释或元素正文；其偏移均按字节计算。 */
    private string $pending = '';
    /** @var string body 对应的小写元素名，如 script/style/title/textarea；不含开始标签。 */
    private string $element = '';
    /** @var bool 当前 script 是 JSON 数据块，完整值不使用 JS 表达式括号。 */
    private bool $jsonScript = false;
    /** @var string 标签中的 HTML 属性引号，空串表示不处于引号内；不同于 JS/CSS 引号。 */
    private string $quote = '';
    /** @var array<string,bool> 当前标签中含自动值的属性名，当前标签闭合前按名字复核组合值。 */
    private array $dynamicAttributes = [];
    /** @var bool 当前标签需要 URL/事件/style/srcset/meta content 的最终组合复核。 */
    private bool $validateAttributes = false;
    /** @var bool 正文是否含自动插值；用于开启 style 完整内容的运行时校验。 */
    private bool $dynamicBody = false;
    /** @var int SVG/MathML 进入深度；非零时禁用自动值输出和普通 HTML 文本快路径。 */
    private int $foreign = 0;
    /** @var string HTML script 状态：data、escaped、double；与 JS 字符串/注释状态独立。 */
    private string $scriptState = 'data';
    /** @var int 仅退出 double 状态的结束标记在 pending 中的起点，不能把它当成真正闭合。 */
    private int $ignoredScriptEnd = -1;
    /** @var int 当前 raw-text 结束标签候选的正文起点；-1 表示没有候选。 */
    private int $rawEndStart = -1;
    /** @var int 已匹配 </元素名 的字节数，跨 literal/raw/include 保留。 */
    private int $rawEndMatched = 0;
    /** @var bool 元素名及边界已确认，等待下一个 >；不重复扫描已累计的正文。 */
    private bool $rawEndAccepted = false;
    /** @var array<string,mixed>|null 仅 Lexer 编译期源码探测使用的 JS 断点。 */
    private ?array $javascriptCheckpoint = null;
    /** @var array<string,mixed>|null 当前 style 正文的增量词法断点。 */
    private ?array $cssCheckpoint = null;
    /** @var array{0:string,1:string,2:int}|null 属性名、引号、值起点；用于当前属性游标及断点身份。 */
    private ?array $attributeKey = null;
    /** @var array<string,mixed>|null 仅 Lexer 源码探测使用的事件属性断点。 */
    private ?array $attributeJavascriptCheckpoint = null;
    /** @var array<string,mixed>|null 当前 style 属性解码后 CSS 前缀的断点。 */
    private ?array $attributeCssCheckpoint = null;
    /** @var bool 源码分词探测遇到不支持的 JS 时，在当前元素内保留原有原文行为。 */
    private bool $javascriptProbeUnknown = false;
    /** @var bool 与当前事件属性绑定的源码探测失败标记，不用于实际数据编码。 */
    private bool $attributeProbeUnknown = false;
    /** 要检查协议和最终组合的 URL 属性；srcset 与 style 另有组合检查，非域名白名单。 */
    private const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'poster', 'cite', 'background', 'xlink:href', 'data', 'manifest'];

    /**
     * 把可信字面量加入实际渲染流，更新 HTML 状态，必要时延迟输出。
     *
     * 普通文本用 strpos 分段输出；raw-text 用增量结束标签候选处理跨段边界，
     * 无状态变化的连续正文整段追加。尤其不能因为 JS 引号/注释未结束就忽略
     * HTML script 的结束标记，也不能每遇到 > 就重新搜索整个 pending 正文。
     * 该方法只解析输出结构，不识别模板标签，不替可信原文做 HTML 编码。
     *
     * @param string $text 可以是部分标签/正文，后续 literal/value/include 会继续该状态。
     * @param bool $encodedJavascript true 表示 JS 数据已经编码并推进计划，不再将其当作静态 JS 源码积累。
     * @return void
     * @throws \InvalidArgumentException 已组合的动态 URL、style 或正文校验失败。
     */
    public function literal(string $text, bool $encodedJavascript = false): void
    {
        $length = strlen($text);
        if ($length !== 0) $this->revision++;
        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];
            if ($this->state === 'body') {
                if ($this->rawEndStart === -1 && !str_contains(substr($this->pending, -12), '<')) {
                    // script data 中 > 比较和 -- 运算不改变 HTML 状态；escaped/double
                    // 还需观察 -->。尾部有 < 时逐字节完成可能跨段的 <!--、<script 等。
                    $special = $this->element === 'script' && $this->scriptState !== 'data' ? '<>-' : '<';
                    $span = strcspn($text, $special, $i);
                    if ($span > 0) {
                        $chunk = substr($text, $i, $span);
                        $this->pending .= $chunk;
                        if ($this->element === 'script' && !$encodedJavascript) $this->javascriptContext->literal($chunk);
                        $i += $span - 1;
                        continue;
                    }
                }
                $this->pending .= $char;
                if ($this->element === 'script' && !$encodedJavascript) $this->javascriptContext->literal($char);
                $endStart = $this->rawTextEnd($char);
                // HTML 的 escaped/double 状态独立于 JS 语法，按实际输出字符维护。
                if ($this->element === 'script') {
                    if ($this->scriptState === 'data' && $char === '-' && str_ends_with($this->pending, '<!--')) $this->scriptState = 'escaped';
                    elseif ($this->scriptState !== 'data' && $char === '>' && str_ends_with($this->pending, '-->')) $this->scriptState = 'data';
                    elseif ($this->scriptState === 'escaped' && str_contains(" \t\n\f\r/>", $char) && preg_match('~<script[\t\n\f\r />]$~i', substr($this->pending, -8))) $this->scriptState = 'double';
                    elseif ($this->scriptState === 'double' && str_contains(" \t\n\f\r/>", $char) && preg_match('~</script[\t\n\f\r />]$~i', substr($this->pending, -9))) {
                        $this->scriptState = 'escaped';
                        $this->ignoredScriptEnd = strlen($this->pending) - 9;
                    }
                }
                // HTML 的 raw-text 结束标签不受 JS/CSS 引号影响。
                if ($endStart !== null) {
                    // double 中的首次 </script> 只退出双重转义，不能提前把后续值当成 HTML。
                    if ($this->element === 'script' && ($this->scriptState === 'double' || $endStart === $this->ignoredScriptEnd)) continue;
                    if ($this->element === 'style' && $this->dynamicBody) CodeContext::validateCss(substr($this->pending, 0, $endStart));
                    echo $this->pending;
                    $this->pending = ''; $this->state = 'text'; $this->element = ''; $this->dynamicBody = false;
                    $this->javascriptContext = null; $this->jsonScript = false;
                }
                continue;
            }
            if ($this->state === 'comment') {
                $this->pending .= $char;
                if (str_ends_with($this->pending, '-->')) { echo $this->pending; $this->pending = ''; $this->state = 'text'; }
                continue;
            }
            if ($this->state === 'text') {
                $nextTag = strpos($text, '<', $i);
                if ($nextTag === false) { echo substr($text, $i); return; }
                if ($nextTag > $i) echo substr($text, $i, $nextTag - $i);
                $this->state = 'tag'; $this->pending = '<'; $this->quote = ''; $i = $nextTag;
                continue;
            }
            // 离开当前 HTML 属性时立即使游标/断点失效，不能跨属性复用语言状态。
            if ($this->attributeKey !== null) {
                $attributeQuote = $this->attributeKey[1];
                if (($attributeQuote !== '' && $char === $attributeQuote) || ($attributeQuote === '' && str_contains(" \t\r\n\f>\"'", $char))) {
                    $this->attributeKey = null; $this->attributeJavascriptCheckpoint = null; $this->attributeCssCheckpoint = null; $this->attributeProbeUnknown = false;
                    $this->attributeJavascriptContext = null; $this->attributeJavascriptLiteral = '';
                } elseif ($this->attributeJavascriptContext !== null && !$encodedJavascript) {
                    $this->attributeJavascriptLiteral .= $char;
                }
            }
            $this->pending .= $char;
            if ($this->pending === '<!--') { $this->state = 'comment'; continue; }
            if (strlen($this->pending) === 2 && !preg_match('/[a-zA-Z\/!?]/', $char)) {
                echo $this->pending; $this->pending = ''; $this->state = 'text'; continue;
            }
            if ($this->quote !== '') { if ($char === $this->quote) $this->quote = ''; continue; }
            if ($char === '"' || $char === "'") { $this->quote = $char; continue; }
            if ($char === '>') $this->closeTag();
        }
    }

    /**
     * 消费刚追加的一个 raw-text 字节，最多推进/重置一个结束标签候选。
     * 名称逐字节匹配且不区分大小写，随后必须是 HTML 空白、/ 或 >；确认边界后
     * 等待 >，保持原有带尾部内容的结束标签规则。原文始终留在 pending，候选只保存
     * 数字游标；不复制剩余正文、不从头搜索。普通 > 无候选时只做常量时间判断。
     * @param string $char 刚追加到 pending 的单字节字符。
     * @return int|null 完成的结束标签在正文中的起点，或尚无完整结束标签。
     */
    private function rawTextEnd(string $char): ?int
    {
        if (!$this->rawEndAccepted) {
            if ($char === '<') {
                $this->rawEndStart = strlen($this->pending) - 1;
                $this->rawEndMatched = 1;
            } elseif ($this->rawEndStart !== -1) {
                $prefix = '</' . $this->element;
                if ($this->rawEndMatched < strlen($prefix)) {
                    if (strtolower($char) === $prefix[$this->rawEndMatched]) $this->rawEndMatched++;
                    else { $this->rawEndStart = -1; $this->rawEndMatched = 0; }
                } elseif (str_contains("\t\n\f\r />", $char)) $this->rawEndAccepted = true;
                else { $this->rawEndStart = -1; $this->rawEndMatched = 0; }
            }
        }
        if ($char !== '>' || !$this->rawEndAccepted) return null;
        $start = $this->rawEndStart;
        $this->rawEndStart = -1; $this->rawEndMatched = 0; $this->rawEndAccepted = false;
        return $start;
    }

    /**
     * 按实际位置编码并输出一个动态值，或把 raw 作为可信原文重新跟踪。
     *
     * HTML 文本、属性和 RCDATA 使用各自的 HTML 编码；script 查询已编译的 JS 计划，
     * style 增量判断 CSS 位置；事件/style 属性在语言编码外再加 HTML 属性编码。
     * 编码后的属性值不会产生新的 HTML 定界符，可直接追加 pending；字符串化及
     * 序列化均受输出缓冲和修订号守卫保护，不允许回调改变已经选定的位置。
     *
     * @param mixed $value 已求值的数据；JS 值允许 JSON 结构，其他位置通常需标量或 Stringable。
     * @param bool $raw true 跳过编码，仍更新后续输出的真实位置；只用于可信内容。
     * @return void
     * @throws \InvalidArgumentException 当前插值位置不支持，类型不合适或值不安全。
     * @throws \JsonException JS JSON 编码失败；异常不回退为 raw。
     * @throws \Throwable 用户字符串化/序列化代码抛出的异常。
     */
    public function value($value, bool $raw = false): void
    {
        if ($value instanceof ModifierValue) {
            $raw = $raw || $value->raw;
            $value = $raw ? $value->value : $this->modifierValue($value);
        }
        if ($raw) { $this->literal($this->stringValue($value)); return; }
        if ($this->foreign > 0) throw new \InvalidArgumentException('Automatic output in SVG/MathML is unsupported; use a trusted raw fragment');
        if ($this->state === 'text') {
            $encoded = self::html($this->stringValue($value));
            $this->revision++; echo $encoded;
            return;
        }
        if ($this->state === 'body') {
            if ($this->element === 'script') { $this->javascriptValue($value, $this->javascriptContext->context()); return; }
            elseif ($this->element === 'style') $encoded = $this->encodeValue($value, CodeContext::css($this->pending, false, $this->cssCheckpoint));
            elseif (in_array($this->element, ['textarea', 'title'], true)) $encoded = self::html($this->stringValue($value));
            else throw new \InvalidArgumentException('Unsupported raw-text element');
            $this->dynamicBody = true;
            $this->literal($encoded);
            return;
        }
        if ($this->state !== 'tag') throw new \InvalidArgumentException('Dynamic output in HTML comments requires raw');
        $attribute = $this->currentAttribute();
        if ($attribute === null) throw new \InvalidArgumentException('Dynamic tag/attribute names or markup require raw');
        [$name, $prefix, $quote] = $attribute;
        $this->attributeValue($value, $name, $prefix, $quote);
    }

    /**
     * 从候选 Context 提取可用于代码生成的 HTML 文本或属性计划。
     *
     * Compiler 仅在以固定占位值驱动的预测对象上调用，所以写入编译文件的信息不含
     * 用户数据。不要把实际请求流对象返回的 pending 快照持久化成编译计划。
     * JS 正文生成编码位置候选；其它正文、注释、动态标签名等按通用路径处理。
     * @return array{kind?:string,tag?:string,attribute?:array{string,string,string},plain?:bool,url?:bool,context?:array{kind:string,regexStart?:bool}} 空数组表示不预测。
     */
    public function valueHint(): array
    {
        if ($this->isText()) return ['kind' => 'html'];
        if ($this->state === 'body' && $this->element === 'script' && $this->foreign === 0) {
            try { return ['kind' => 'javascript', 'context' => $this->javascriptContext->context()]; }
            catch (\InvalidArgumentException $error) { return []; }
        }
        if ($this->state === 'tag' && $this->foreign === 0) {
            $attribute = $this->currentAttribute();
            if ($attribute !== null) {
                [$name, $prefix, $quote] = $attribute;
                $url = in_array($name, self::URL_ATTRIBUTES, true);
                $plain = $quote !== '' && $prefix === '' && !str_starts_with($name, 'on')
                    && !in_array($name, ['style', 'srcset', 'imagesrcset', 'content', 'srcdoc', 'xmlns', 'http-equiv', 'charset'], true)
                    && !$url;
                return ['kind' => 'attribute', 'tag' => $this->pending, 'attribute' => $attribute, 'plain' => $plain, 'url' => $url];
            }
        }
        return [];
    }

    /**
     * 仅供编译器在全新 Context 中预演静态字面量后提取出口。
     * 自动值、外来元素、注释或 raw-text 均不导出，不能把请求流中的属性值固化到计划。
     * @return array{mode?:string,tag?:string,quote?:string} 空数组表示常规解析。
     */
    public function literalHint(): array
    {
        if ($this->isText()) return ['mode' => 'text'];
        if ($this->state === 'tag' && $this->foreign === 0 && $this->dynamicAttributes === []) {
            return ['mode' => 'open', 'tag' => $this->pending, 'quote' => $this->quote];
        }
        return [];
    }

    /**
     * 执行编译器预演的静态 HTML 转换；入口不匹配时保留逐字符解析。
     * open 只从干净文本入口初始化静态标签前缀，后续动态值仍经 attributeValue 编码。
     * close 只消费当前实际引号及 >，实际标签仍交 closeTag 完整校验；不能假定它一定
     * 是编译期预测的普通标签，可能进入 script、style 或 SVG，余下文本须重新守卫。
     * @param string $text 编译计划对应的可信模板字面量。
     * @param array{mode?:'text'|'open'|'close',tag?:string,quote?:string,output?:string,after?:array} $plan 静态转换计划；close 的 after 是剩余片段的计划。
     * @return void
     */
    public function plannedLiteral(string $text, array $plan): void
    {
        if ($text === '') return;
        $mode = $plan['mode'] ?? '';
        if ($mode === 'close') {
            if ($this->state !== 'tag' || $this->foreign !== 0 || $this->quote !== $plan['quote']) {
                $this->literal($text); return;
            }
            $this->revision++; $this->pending .= $plan['quote'] . '>'; $this->quote = '';
            $this->closeTag();
            $tail = substr($text, 2);
            if ($tail !== '') $this->plannedLiteral($tail, $plan['after']);
            return;
        }
        if (!$this->isText()) { $this->literal($text); return; }
        if ($mode === 'text') { $this->revision++; echo $text; return; }
        if ($mode === 'open') {
            $this->revision++; echo $plan['output'];
            $this->state = 'tag'; $this->pending = $plan['tag']; $this->quote = $plan['quote'];
            return;
        }
        $this->literal($text);
    }

    /**
     * 执行编译器选择的 HTML 文本输出候选，不匹配时回退通用编码。
     *
     * 快路径仅接受普通文本入口中的标量/null。Stringable 等对象不能按无副作用的
     * 标量处理，数组也不能因为预测为文本就跳过类型检查。
     * @param mixed $value 运行时表达式的结果。
     * @return void
     * @throws \Throwable 通用编码路径或字符串转换抛出的异常。
     */
    public function htmlValue($value): void
    {
        if ($this->isText() && (is_scalar($value) || $value === null)) { $this->revision++; echo self::html(CodeContext::string($value)); return; }
        $this->value($value);
    }

    /**
     * 运行编译文件保存的 JS 编码调用；实际入口以共享计划集校验，不运行词法扫描器。
     * 模板表达式可以包含嵌套 include，实参求值后必须重新读取实际上下文。
     * @param mixed $value 已求值的数据或带修饰器意图的 ModifierValue。
     * @param array{kind:string,regexStart?:bool} $hint 编译期预测的 JS 编码位置。
     * @return void
     */
    public function javascriptPlannedValue($value, array $hint): void
    {
        if ($value instanceof ModifierValue || $this->foreign !== 0 || $this->state !== 'body' || $this->element !== 'script') {
            $this->value($value); return;
        }
        $actual = $this->javascriptContext->context();
        $this->javascriptValue($value, $actual === $hint ? $hint : $actual);
    }

    /**
     * 执行计划已确定的 JS 编码，并用短占位符推进后续计划，不重新分析编码后的数据。
     * @param mixed $value 待按当前 JS/JSON 位置编码的数据。
     * @param array{kind:string,regexStart?:bool} $context 已校验的 JS 编码位置，JSON script 中转换为对应 JSON 位置。
     * @return void
     */
    private function javascriptValue($value, array $context): void
    {
        if ($this->jsonScript) {
            if ($context['kind'] === 'js-value') $context = ['kind' => 'json-value'];
            elseif ($context['kind'] === 'js-double') $context = ['kind' => 'json-string'];
            else throw new \InvalidArgumentException('Dynamic JSON output requires a value or double-quoted string');
        }
        $encoded = $this->encodeValue($value, $context);
        $this->javascriptContext->value($encoded, $context);
        $this->dynamicBody = true;
        $this->literal($encoded, true);
    }

    /**
     * 把文本/属性中的 Stringable 转成字符串；标量无需缓冲，用户转换必须受守卫保护。
     * @param mixed $value 待转换的数据；具体可接受类型由 CodeContext::string() 判断。
     * @return string 尚未进行 HTML 编码的字符串。
     */
    private function stringValue($value): string
    {
        if (!is_object($value)) return CodeContext::string($value);
        return $this->guardConversion(static fn() => CodeContext::string($value));
    }

    /**
     * JS/JSON/CSS 编码均可能执行用户转换或序列化，不能中途改变输出位置。
     * @param mixed $value 待编码的数据；可接受类型由编码位置决定。
     * @param array{kind:string,prefix?:string,quote?:string,regexStart?:bool} $context JS、JSON 或 CSS 编码位置。
     * @return string 完成语言层编码、尚未添加 HTML 属性编码的源码片段。
     */
    private function encodeValue($value, array $context): string
    {
        if (!is_object($value) && !is_array($value)) return CodeContext::encode($value, $context);
        return $this->guardConversion(static fn() => CodeContext::encode($value, $context));
    }

    /**
     * 拒绝转换回调直接输出、嵌套渲染改变上下文或修改缓冲层级；异常时丢弃捕获的内容。
     * 修订号也覆盖属性快路径的直接追加，不能只比较转换前后的状态名称或输出缓冲。
     * @param callable():string $convert 只返回编码结果、不改变输出流的转换回调。
     * @return string 回调返回的编码结果。
     */
    private function guardConversion(callable $convert): string
    {
        $revision = $this->revision; $level = ob_get_level(); ob_start();
        try {
            $encoded = $convert();
            if ($this->revision !== $revision || ob_get_level() !== $level + 1 || ob_get_contents() !== '') throw new \LogicException('Template encoding callbacks must not change the output context or emit content');
            return $encoded;
        } finally {
            while (ob_get_level() > $level) if (!ob_end_clean()) break;
        }
    }

    /**
     * 用编译计划省去属性位置的重复解析，但仍执行动态值及组合值检查。
     *
     * 入口必须是普通 HTML 标签状态，且 pending 与预测前缀逐字节一致；否则重新
     * 判断实际上下文。hint 只由可信编译代码提供，不接受来自请求的编码类型指令。
     * @param mixed $value 已求值的数据。
     * @param array{tag:string,attribute:array{string,string,string},plain?:bool,url?:bool} $hint 标签前缀及编译期属性分类。
     * @return void
     * @throws \Throwable 属性编码或回退路径失败。
     */
    public function attributePlannedValue($value, array $hint): void
    {
        if ($value instanceof ModifierValue) { $this->value($value); return; }
        if ($this->state === 'tag' && $this->foreign === 0 && $this->pending === $hint['tag']) {
            [$name, $prefix, $quote] = $hint['attribute'];
            // 仅静态空前缀、带引号的普通属性可直接 HTML 编码；对象仍进入转换守卫。
            if (($hint['plain'] ?? false) && (is_scalar($value) || $value === null)) {
                $encoded = self::html(CodeContext::string($value));
                $this->dynamicAttributes[$name] = true;
                $this->revision++; $this->pending .= $encoded;
                return;
            }
            $this->attributeValue($value, $name, $prefix, $quote); return;
        }
        $this->value($value);
    }

    /**
     * 末尾 HTML escape 由真实输出位置重新编码，保留数据含义且避免重复编码。
     * 末尾 escape:url 在 URL 属性已有静态前缀时交给组件编码；其它位置保留转换结果。
     * URL 协议、属性组合等检查仍由正常 value/attributeValue 路径执行。
     * @param ModifierValue $value 修饰链结果、转义前原值及最后一个编码意图。
     * @return mixed 根据实际位置选出的原值或修饰链结果，后续仍需上下文编码。
     */
    private function modifierValue(ModifierValue $value)
    {
        if ($value->escape === 'html') return $value->beforeEscape;
        if ($value->escape === 'url' && $this->state === 'tag') {
            $attribute = $this->currentAttribute();
            if ($attribute !== null && in_array($attribute[0], self::URL_ATTRIBUTES, true) && $attribute[1] !== '') return $value->beforeEscape;
        }
        return $value->value;
    }

    /**
     * 编码当前属性值，并标记该属性在标签闭合时需要组合校验。
     *
     * HTML 实体解码只用于判定事件/style/URL 前缀的真实语义，不直接输出解码结果。
     * 敏感属性要求完整、可识别的实体引用，拒绝 PHP 与浏览器可能解码不同的形式。
     * 插值前未完成的实体前缀先按字面量保护，避免动态后缀在浏览器中组成新引号。
     * 同一事件属性复用 JS 编译计划，style 前缀复用增量断点；属性改变时重置。
     *
     * @param mixed $value 已求值的输入，编码类型由属性及前缀决定。
     * @param string $name 小写属性名。
     * @param string $prefix 当前属性值内已累积的 HTML 源码，尚未做实体解码。
     * @param string $quote HTML 包围引号，空串为无引号属性。
     * @return void
     * @throws \InvalidArgumentException 属性、协议、类型或空的无引号插值不受支持。
     * @throws \Throwable JS/CSS 编码及用户转换代码的异常。
     */
    private function attributeValue($value, string $name, string $prefix, string $quote): void
    {
        if (in_array($name, ['srcdoc', 'xmlns', 'http-equiv', 'charset'], true)) throw new \InvalidArgumentException('Unsupported dynamic HTML attribute: ' . $name);
        $key = [$name, $quote, strlen($this->pending) - strlen($prefix)];
        if ($this->attributeKey !== $key) {
            $this->attributeKey = $key;
            $this->attributeJavascriptCheckpoint = null; $this->attributeCssCheckpoint = null; $this->attributeProbeUnknown = false;
            $this->attributeJavascriptContext = null; $this->attributeJavascriptLiteral = '';
        }
        // 静态的 &quo + 动态的 t; 不能在浏览器中重新组成一个引号实体。
        if (preg_match('/&(?:#[xX][0-9a-fA-F]*|#[0-9]*|[a-zA-Z][a-zA-Z0-9]*)?$/', $prefix, $entity, PREG_OFFSET_CAPTURE)) {
            $position = $entity[0][1];
            $safePrefix = substr_replace($prefix, '&amp;', $position, 1);
            $this->pending = substr($this->pending, 0, strlen($this->pending) - strlen($prefix)).$safePrefix;
            $prefix = $safePrefix;
            if (preg_match('/&(?:#[xX][0-9a-fA-F]*|#[0-9]*|[a-zA-Z][a-zA-Z0-9]*)?$/', $this->attributeJavascriptLiteral, $literalEntity, PREG_OFFSET_CAPTURE)) {
                $this->attributeJavascriptLiteral = substr_replace($this->attributeJavascriptLiteral, '&amp;', $literalEntity[0][1], 1);
            }
        }
        $sensitive = str_starts_with($name, 'on') || $name === 'style' || in_array($name, self::URL_ATTRIBUTES, true);
        $decoded = $sensitive ? self::decodeAttribute($prefix) : html_entity_decode($prefix, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (str_starts_with($name, 'on')) {
            if ($this->attributeJavascriptContext === null) {
                $this->attributeJavascriptContext = new JsContext($this->javascriptPlanCache);
                $this->attributeJavascriptContext->literal($decoded);
            } else $this->attributeJavascriptContext->literal(self::decodeAttribute($this->attributeJavascriptLiteral));
            $context = $this->attributeJavascriptContext->context();
            $encoded = $this->encodeValue($value, $context);
            $this->attributeJavascriptContext->value($encoded, $context);
            $this->attributeJavascriptLiteral = '';
        }
        elseif ($name === 'style') $encoded = $this->encodeValue($value, CodeContext::css($decoded, true, $this->attributeCssCheckpoint));
        elseif (in_array($name, self::URL_ATTRIBUTES, true)) {
            $encoded = $this->stringValue($value);
            if ($decoded === '') CodeContext::url($encoded);
            else $encoded = rawurlencode($encoded);
        } else $encoded = $this->stringValue($value);
        if ($quote === '' && $prefix === '' && $encoded === '') throw new \InvalidArgumentException('Empty dynamic attributes require quotes');
        $this->dynamicAttributes[$name] = true;
        if ($sensitive || in_array($name, ['srcset', 'imagesrcset', 'content'], true)) $this->validateAttributes = true;
        $encoded = self::html($encoded);
        if ($quote === '') $encoded = preg_replace_callback('/[\x00-\x20`=\x7f]/', static fn($match) => '&#' . ord($match[0]) . ';', $encoded);
        // HTML 属性编码不产生实际引号、尖括号或分隔空白，无需再逐字节扫描。
        $this->revision++; $this->pending .= $encoded;
    }

    /**
     * 判断能否使用普通 HTML 文本的编译快路径或插入可信 part。
     * @return bool 词法状态为 text 且不在 SVG/MathML 中；不表示 DOM 节点已全部闭合。
     */
    public function isText(): bool { return $this->state === 'text' && $this->foreign === 0; }

    /**
     * 编译后的完整 HTML 块只从实际文本入口输出；修订号仍使外层转换回调守卫生效。
     * @return bool true 表示当前是普通 HTML 文本入口，并已推进修订号。
     */
    public function beginHtml(): bool
    {
        if (!$this->isText()) return false;
        $this->revision++;
        return true;
    }

    /**
     * 在最外层渲染结束时检查词法状态，不对普通 DOM 树做闭合修复。
     *
     * 单独的尾部 < 按普通文本发出；未完成的标签、注释或 raw-text 正文则报错。
     * include 不单独调用此方法，允许子模板承担父模板属性/正文的一部分。
     * @return void
     * @throws \InvalidArgumentException 最外层停在未完成的词法结构中。
     */
    public function finish(): void
    {
        if ($this->state === 'tag' && $this->pending === '<') { echo '<'; $this->pending = ''; $this->state = 'text'; }
        if ($this->state !== 'text') throw new \InvalidArgumentException('Template ends inside an HTML tag, comment or raw-text element');
    }

    /**
     * 语言/URL 属性采用受支持的完整 HTML 引用，拒绝 PHP 与浏览器可能解释不同的引用。
     * 不自行猜测缺失的分号；未知或原生 HTML5 解码器不接受的引用同样拒绝。
     * 无分号的命名串后跟等号时按 HTML 属性规则保留原文，不会把普通 &page= 当成引用。
     * @param string $source 未做实体解码的 HTML 属性源码。
     * @return string 解码一次的属性文本，不对生成的 & 再次递归解码。
     * @throws \InvalidArgumentException 遇到不支持的引用；必须在选取编码器前拒绝。
     */
    private static function decodeAttribute(string $source): string
    {
        if (!str_contains($source, '&')) return $source;
        preg_match_all('/&(?:#[xX][0-9a-fA-F]+|#[0-9]+|[A-Za-z][A-Za-z0-9]*);?/', $source, $references, PREG_OFFSET_CAPTURE);
        foreach ($references[0] as [$reference, $offset]) {
            if (!str_ends_with($reference, ';')) {
                if ($reference[1] !== '#' && ($source[$offset + strlen($reference)] ?? '') === '=') continue;
                throw new \InvalidArgumentException('Unsupported semicolonless HTML attribute reference');
            }
            if (html_entity_decode($reference, ENT_QUOTES | ENT_HTML5, 'UTF-8') === $reference) {
                throw new \InvalidArgumentException('Unsupported HTML attribute reference');
            }
        }
        return html_entity_decode($source, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * 仅供 Lexer 判断源码中的美元花括号是否位于 JS 模板字符串，绝不作为编码依据。
     * 探测不执行模板表达式；不支持的 JS 源码保留该 JS 作用域内原先的原文规则。
     * @return bool 模板字符串中的原生插值，或无法确定的 JS 源码；普通 HTML 始终 false。
     */
    public function javascriptTemplateSource(): bool
    {
        if ($this->state === 'body' && $this->element === 'script') {
            if ($this->jsonScript) return false;
            if ($this->javascriptProbeUnknown) return true;
            try { return CodeContext::javascript($this->pending, $this->javascriptCheckpoint, false)['kind'] === 'js-template'; }
            catch (\InvalidArgumentException $error) { $this->javascriptProbeUnknown = true; return true; }
        }
        if ($this->state === 'tag') {
            $attribute = $this->currentAttribute();
            if ($attribute !== null && str_starts_with($attribute[0], 'on')) {
                [$name, $prefix, $quote] = $attribute;
                $key = [$name, $quote, strlen($this->pending) - strlen($prefix)];
                if ($this->attributeKey !== $key) {
                    $this->attributeKey = $key; $this->attributeJavascriptCheckpoint = null; $this->attributeProbeUnknown = false;
                }
                if ($this->attributeProbeUnknown) return true;
                try { return CodeContext::javascript(self::decodeAttribute($prefix), $this->attributeJavascriptCheckpoint, false)['kind'] === 'js-template'; }
                catch (\InvalidArgumentException $error) { $this->attributeProbeUnknown = true; return true; }
            }
        }
        return false;
    }

    /**
     * 统一的 UTF-8 HTML 文本/属性基础编码，替换非法字节并覆盖单双引号。
     * @param string $value 已完成标量/Stringable 转换的字符串。
     * @return string 包含 HTML 实体的字符串；不处理 URL 协议、JS 或 CSS 语义。
     */
    private static function html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * 复用当前属性的值起点，避免多次插值时从标签开头重扫。
     *
     * literal 在结束引号/无引号分隔符处使游标失效，closeTag 也会清理；
     * 无有效游标时退回完整 attribute 扫描，不能仅凭上一次属性名沿用编码类型。
     * @return array{string,string,string}|null 属性名、原始值前缀、引号，或不是属性值位置。
     */
    private function currentAttribute(): ?array
    {
        if ($this->attributeKey !== null) {
            [$name, $quote, $start] = $this->attributeKey;
            return [$name, substr($this->pending, $start), $quote];
        }
        return self::attribute($this->pending);
    }

    /**
     * 从尚未关闭的开始标签推导末尾是否处于属性值，支持前置布尔属性。
     *
     * 读取属性名、等号、引号及值边界；完整闭合的前面属性会跳过，当前值才作为前缀返回。
     * 不允许动态生成标签名或属性名；这里的原始前缀不进行 HTML 实体解码。
     * @param string $tag 从 < 开始、尚未发出的标签源码。
     * @return array{string,string,string}|null 小写属性名、值前缀及引号，或无法确认属性值位置。
     */
    private static function attribute(string $tag): ?array
    {
        if (!preg_match('/\A<[a-zA-Z][a-zA-Z0-9:-]*\s+/', $tag, $head)) return null;
        $offset = strlen($head[0]); $length = strlen($tag);
        while ($offset < $length) {
            if (!preg_match('/\G([^\s=\/>]+)\s*/', $tag, $match, 0, $offset)) return null;
            $name = strtolower($match[1]); $offset += strlen($match[0]);
            if ($offset >= $length) return null;
            if ($tag[$offset] !== '=') continue; // 前面的 disabled/checked 等布尔属性。
            $offset++;
            while ($offset < $length && str_contains(" \t\r\n\f", $tag[$offset])) $offset++;
            if ($offset === $length) return [$name, '', ''];
            $quote = $tag[$offset] === '"' || $tag[$offset] === "'" ? $tag[$offset++] : '';
            $start = $offset;
            while ($offset < $length && ($quote !== '' ? $tag[$offset] !== $quote : !str_contains(" \t\r\n\f>", $tag[$offset]))) $offset++;
            if ($offset === $length) return [$name, substr($tag, $start), $quote];
            if ($quote !== '') $offset++;
            while ($offset < $length && str_contains(" \t\r\n\f", $tag[$offset])) $offset++;
        }
        return null;
    }

    /**
     * 在未处于 HTML 引号内遇到 > 时验证并输出整个待发标签。
     *
     * 含动态数据的 URL/srcset/style 使用最终拼接结果复核，防止单独变量检查被
     * 静态后缀或其他变量绕过；拒绝动态 meta refresh 等不支持的组合。
     * 校验前清理已结束属性的游标/断点，异常时也不能沿用旧属性；发出标签后更新外来元素深度。
     * 进入新的 raw-text 元素时
     * 重新初始化正文断点，避免把旧元素的偏移与词法状态套到新元素上。
     * @return void
     * @throws \InvalidArgumentException 组合属性不安全；异常发生在本标签发出之前。
     */
    private function closeTag(): void
    {
        $tag = $this->pending;
        $this->attributeKey = null; $this->attributeJavascriptCheckpoint = null; $this->attributeCssCheckpoint = null; $this->attributeProbeUnknown = false;
        $this->attributeJavascriptContext = null; $this->attributeJavascriptLiteral = '';
        if ($this->validateAttributes) {
            preg_match_all('~([^\s=<>/]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))~', $tag, $attributes, PREG_SET_ORDER);
            $values = [];
            foreach ($attributes as $attribute) {
                $name = strtolower($attribute[1]);
                $rawValue = $attribute[2] !== '' ? $attribute[2] : (($attribute[3] ?? '') !== '' ? $attribute[3] : ($attribute[4] ?? ''));
                $sensitive = isset($this->dynamicAttributes[$name]) && (str_starts_with($name, 'on') || $name === 'style' || $name === 'srcset' || $name === 'imagesrcset' || in_array($name, self::URL_ATTRIBUTES, true));
                $value = $sensitive ? self::decodeAttribute($rawValue) : html_entity_decode($rawValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $values[$name] ??= $value;
                if (!isset($this->dynamicAttributes[$name])) continue;
                if (in_array($name, self::URL_ATTRIBUTES, true)) CodeContext::url($value);
                elseif ($name === 'srcset' || $name === 'imagesrcset') {
                    foreach (explode(',', $value) as $candidate) {
                        $url = preg_split('/\s+/', trim($candidate))[0]; CodeContext::url($url);
                    }
                } elseif ($name === 'style') CodeContext::validateCss($value);
            }
            if (preg_match('/^<meta\b/i', $tag) && isset($this->dynamicAttributes['content']) && strtolower(trim($values['http-equiv'] ?? '')) === 'refresh') {
                throw new \InvalidArgumentException('Dynamic meta refresh content is unsupported; use a URL attribute');
            }
        }
        $jsonScript = self::scriptUsesJson($tag);
        echo $tag;
        $this->pending = ''; $this->state = 'text'; $this->quote = ''; $this->dynamicAttributes = []; $this->validateAttributes = false;
        if (!preg_match('~^<(/?)([a-zA-Z][a-zA-Z0-9:-]*)~', $tag, $match)) return;
        $name = strtolower($match[2]);
        if ($name === 'svg' || $name === 'math') {
            if ($match[1] === '/') $this->foreign = max(0, $this->foreign - 1);
            elseif (!str_ends_with($tag, '/>')) $this->foreign++;
        }
        if ($match[1] === '' && in_array($name, ['script', 'style', 'textarea', 'title', 'xmp', 'iframe', 'noembed', 'noframes', 'plaintext'], true)) {
            $this->state = 'body'; $this->element = $name; $this->dynamicBody = false;
            $this->jsonScript = $name === 'script' && $jsonScript;
            $this->rawEndStart = -1; $this->rawEndMatched = 0; $this->rawEndAccepted = false;
            $this->scriptState = 'data'; $this->ignoredScriptEnd = -1;
            $this->javascriptCheckpoint = null; $this->cssCheckpoint = null; $this->javascriptProbeUnknown = false;
            $this->javascriptContext = $name === 'script' ? new JsContext($this->javascriptPlanCache) : null;
        }
    }

    /**
     * 按 HTML 属性边界读取首个 type（包括空值/布尔属性），不匹配其它属性值中的文字。
     * JSON MIME（含 +json）、importmap/speculationrules 使用 JSON；其它类型保留 JS 路径。
     * MIME 参数不影响 JSON 识别；实体先采用敏感属性规则解码，不猜测浏览器解码差异。
     * @param string $tag 完整的 HTML 标签原文。
     * @return bool 是否为应使用 JSON 编码规则的 script 开始标签。
     */
    private static function scriptUsesJson(string $tag): bool
    {
        if (!preg_match('~\A<script(?=[\t\n\f\r />])~i', $tag, $head)) return false;
        $offset = strlen($head[0]); $length = strlen($tag);
        while ($offset < $length) {
            $offset += strspn($tag, "\t\n\f\r /", $offset);
            if ($offset >= $length || $tag[$offset] === '>') break;
            $start = $offset;
            $offset += strcspn($tag, "\t\n\f\r />=", $offset);
            if ($offset === $start) { $offset++; continue; }
            $name = strtolower(substr($tag, $start, $offset - $start));
            $offset += strspn($tag, "\t\n\f\r ", $offset);
            $value = '';
            if (($tag[$offset] ?? '') === '=') {
                $offset++;
                $offset += strspn($tag, "\t\n\f\r ", $offset);
                $quote = $tag[$offset] ?? '';
                if ($quote === '"' || $quote === "'") {
                    $start = ++$offset;
                    $end = strpos($tag, $quote, $offset);
                    $offset = $end === false ? $length : $end;
                    $value = substr($tag, $start, $offset - $start); $offset++;
                } else {
                    $start = $offset;
                    $offset += strcspn($tag, "\t\n\f\r >", $offset);
                    $value = substr($tag, $start, $offset - $start);
                }
            }
            if ($name !== 'type') continue;
            $type = strtolower(trim(self::decodeAttribute($value), "\t\n\f\r "));
            if (in_array($type, ['importmap', 'speculationrules'], true)) return true;
            $mime = trim(explode(';', $type, 2)[0], "\t\n\f\r ");
            return in_array($mime, ['application/json', 'text/json'], true)
                || (bool) preg_match('~\A[a-z0-9!#$&^_.+\x2d]+/[a-z0-9!#$&^_.+\x2d]+\+json\z~D', $mime);
        }
        return false;
    }
}
