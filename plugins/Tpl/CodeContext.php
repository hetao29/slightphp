<?php
namespace SlightPHP\Tpl;

/**
 * JS/CSS 值位置识别、JS/JSON/CSS 上下文编码及 URL/CSS 组合检查。
 *
 * javascript/css 接收完整的已有前缀，但可从 checkpoint 继续词法扫描，避免每次插值
 * 都从头重扫。断点只适用于同一个字节前缀不断追加的流；更换元素、属性或源串时必须
 * 重置为 null，不能把它当作按字符串长度通用复用的缓存。
 *
 * 返回的 kind 是编码位置，不是完整语言语法验证结果。JS 识别候选值、字符串、模板
 * 字符串及正则位置，CSS 识别值、标识符、字符串及 URL；不执行任何 JS/CSS，也不构建 AST。
 * 实际 HTML 状态、属性实体解码和输出时机由 Context 管理。
 *
 * @internal checkpoint 是实现细节；应用只应通过 Tpl 的自动模式使用这些规则。
 */
final class CodeContext
{
    /**
     * 判断当前 JS 前缀末尾的数据插入位置，可回写增量扫描断点。
     *
     * 状态包括 code、单/双引号、template、regex、行/块注释；expect 表示是否允许一个
     * 新的值操作数，characterClass 记录正则字符类，templates 保存嵌套模板表达式的
     * 花括号层数。后置 ++/-- 保留“已有操作数”状态，避免将除法误识别为正则开始。
     * parentheses 区分控制条件与普通调用/分组的圆括号，控制条件后的 / 可以开始正则；
     * 连续三个点按展开运算符处理，其后允许完整操作数；单个点仍要求成员名。
     * pendingControl/member 避免把 obj.if() 等成员名误当控制关键字。lineStart 保留
     * HTML 风格行注释所需的行首状态，包含垂直制表符在内的 ASCII 空白不清除此状态。
     * 无法确定的 }/、跨行操作数后 /、await/yield/of 后 / 明确拒绝，不猜测其语义。
     *
     * 断点通常位于末尾 8 字节附近的安全扫描边界；长标识符/数字可能回退到 token
     * 开头。它不是机械截断的尾字符串，必须同时保存该位置的完整词法状态。
     * 不提供断点时从头扫描。即使末尾位置不支持插值也可能已回写断点，不能把回写
     * 当作校验成功；错误时必须阻止该位置的数据输出。
     *
     * @param string $source JS 元素内容或 HTML 实体解码后的事件属性前缀。
     * @param array<string,mixed>|null $checkpoint 原地读写的 offset/state/expect/escaped/栈等快照。
     * @param bool $validate 编译器/Lexer 探测为 false，返回资格标记供编译计划验证；直接插值必须为 true。
     * @return array{kind:string,regexStart?:bool,expect?:bool,escaped?:bool} regexStart 标记正则起始位置；false 时另含插值资格。
     * @throws \InvalidArgumentException 插值位置不支持，或遇到歧义斜杠、嵌套正则字符类及不支持的标识符。
     */
    public static function javascript(string $source, ?array &$checkpoint = null, bool $validate = true): array
    {
        // checkpoint 由 Context 持有，仅用于同一个只追加的脚本流。
        $resume = $checkpoint ?? [];
        $state = $resume['state'] ?? 'code';
        $expect = $resume['expect'] ?? true;
        $escaped = $resume['escaped'] ?? false;
        $characterClass = $resume['characterClass'] ?? false;
        $regexStart = $resume['regexStart'] ?? false;
        $templates = $resume['templates'] ?? [];
        $parentheses = $resume['parentheses'] ?? [];
        $pendingControl = $resume['pendingControl'] ?? null;
        $member = $resume['member'] ?? false;
        $ambiguousSlash = $resume['ambiguousSlash'] ?? false;
        $statementEnd = $resume['statementEnd'] ?? false;
        $lineStart = $resume['lineStart'] ?? true;
        $offset = $resume['offset'] ?? 0;
        $length = strlen($source);
        $boundary = max($offset, $length - 8);
        $anchor = null;
        for ($i = $offset; $i < $length; $i++) {
            // 只保存一次边界状态；下次重读末尾少量字符，覆盖跨段的 /*、++、${ 等。
            if ($anchor === null && $i >= $boundary) $anchor = compact('i', 'state', 'expect', 'escaped', 'characterClass', 'regexStart', 'templates', 'parentheses', 'pendingControl', 'member', 'ambiguousSlash', 'statementEnd', 'lineStart');
            $char = $source[$i];
            $next = $source[$i + 1] ?? '';
            $unicodeBreak = $char === "\xE2" && in_array(substr($source, $i, 3), ["\xE2\x80\xA8", "\xE2\x80\xA9"], true);
            $lineBreak = $char === "\n" || $char === "\r" || $unicodeBreak;
            if ($lineBreak && in_array($state, ['code', 'line', 'comment'], true)) {
                $lineStart = true;
                if ($statementEnd) { $expect = true; $statementEnd = false; }
                if ($state === 'line') $state = 'code';
                if ($unicodeBreak) $i += 2;
                continue;
            }
            if ($state === 'line') continue;
            if ($state === 'comment') { if ($char === '*' && $next === '/') { $state = 'code'; $i++; } continue; }
            if (in_array($state, ['single', 'double', 'template', 'regex'], true)) {
                if ($state === 'regex') $regexStart = false;
                if ($escaped) { $escaped = false; continue; }
                if ($char === '\\') { $escaped = true; continue; }
                if ($state === 'regex') {
                    if ($char === '[') {
                        if ($characterClass) throw new \InvalidArgumentException('Nested JS regex character classes are unsupported');
                        $characterClass = true;
                    }
                    elseif ($char === ']') $characterClass = false;
                    elseif ($char === '/' && !$characterClass) { $state = 'code'; $expect = false; }
                } elseif ($state === 'template' && $char === '$' && $next === '{') {
                    $templates[] = 0; $state = 'code'; $expect = true; $i++;
                } elseif (($state === 'single' && $char === "'") || ($state === 'double' && $char === '"') || ($state === 'template' && $char === '`')) {
                    $state = 'code'; $expect = false;
                }
                continue;
            }
            if (str_contains(" \t\r\n\f\v", $char)) continue;
            if ($lineStart && substr($source, $i, 3) === '-->') { $state = 'line'; $i += 2; continue; }
            if ($i === 0 && $char === '#' && $next === '!') { $state = 'line'; $i++; continue; }
            if (substr($source, $i, 4) === '<!--') { $state = 'line'; $i += 3; continue; }
            if ($char === '/' && $next === '/') { $state = 'line'; $i++; continue; }
            if ($char === '/' && $next === '*') { $state = 'comment'; $i++; continue; }
            $afterLineBreak = $lineStart; $lineStart = false;
            // ++/-- 的前置形式仍期待操作数，后置形式保留“已有操作数”的状态。
            if (($char === '+' || $char === '-') && $next === $char) {
                $pendingControl = null; $member = false; $ambiguousSlash = false; $statementEnd = false;
                $i++; continue;
            }
            if ($char === "'" || $char === '"' || $char === '`') {
                $pendingControl = null; $member = false; $ambiguousSlash = false; $statementEnd = false;
                $state = $char === "'" ? 'single' : ($char === '"' ? 'double' : 'template');
                continue;
            }
            if ($char === '/' && ($ambiguousSlash || ($afterLineBreak && !$expect))) throw new \InvalidArgumentException('Ambiguous JS slash; disambiguate with parentheses or a statement separator');
            if ($char === '/' && $expect) { $state = 'regex'; $characterClass = false; $regexStart = true; continue; }
            if (preg_match('/[A-Za-z_$][A-Za-z0-9_$]*/A', $source, $match, 0, $i)) {
                if ($anchor === null && $i + strlen($match[0]) === $length) $anchor = compact('i', 'state', 'expect', 'escaped', 'characterClass', 'regexStart', 'templates', 'parentheses', 'pendingControl', 'member', 'ambiguousSlash', 'statementEnd', 'lineStart');
                $word = $match[0];
                $forAwait = !$member && $pendingControl === 'for' && $word === 'await';
                $pendingControl = !$member && in_array($word, ['if', 'while', 'for', 'with', 'switch', 'catch'], true)
                    ? $word : ($forAwait ? 'for' : null);
                $statementEnd = !$member && ($statementEnd || in_array($word, ['break', 'continue', 'debugger'], true));
                // await/yield/of 也可能是普通标识符，紧随的斜杠不能只靠关键字猜测。
                $ambiguousSlash = !$member && in_array($word, ['await', 'yield', 'of'], true) && !$forAwait;
                $expect = !$member && in_array($word, ['return', 'throw', 'case', 'default', 'extends', 'typeof', 'void', 'delete', 'new', 'await', 'yield', 'in', 'of', 'instanceof', 'else', 'do', 'break', 'continue', 'debugger'], true);
                $member = false;
                $i += strlen($match[0]) - 1;
                continue;
            }
            if (preg_match('/[0-9]+(?:\.[0-9]*)?(?:[eE][+-]?[0-9]+)?/A', $source, $match, 0, $i)) {
                if ($anchor === null && $i + strlen($match[0]) === $length) $anchor = compact('i', 'state', 'expect', 'escaped', 'characterClass', 'regexStart', 'templates', 'parentheses', 'pendingControl', 'member', 'ambiguousSlash', 'statementEnd', 'lineStart');
                $pendingControl = null; $member = false; $ambiguousSlash = false; $statementEnd = false;
                $expect = false; $i += strlen($match[0]) - 1; continue;
            }
            // # 标记 private identifier；其后的关键字形状也必须按成员名处理。
            if ($char === '#') { $member = true; $pendingControl = null; $statementEnd = false; $expect = false; continue; }
            if ($char === '\\' || ord($char) >= 0x80) throw new \InvalidArgumentException('Unsupported escaped or non-ASCII JS identifier');
            $control = $pendingControl; $pendingControl = null; $member = false; $statementEnd = false;
            if ($char === '(') {
                $parentheses[] = $control !== null ? 'control' : 'expression';
                $expect = true; $ambiguousSlash = false;
            } elseif ($char === ')') {
                $role = array_pop($parentheses);
                $expect = $role === 'control';
                $ambiguousSlash = $role === null;
            } elseif ($char === '{') {
                if ($templates !== []) $templates[count($templates) - 1]++;
                $expect = true; $ambiguousSlash = false;
            } elseif ($char === '}') {
                $expect = false; $ambiguousSlash = true;
                if ($templates !== []) {
                    $last = count($templates) - 1;
                    if ($templates[$last] === 0) { array_pop($templates); $state = 'template'; $ambiguousSlash = false; }
                    else $templates[$last]--;
                }
            } elseif ($char === ']') { $expect = false; $ambiguousSlash = false; }
            elseif ($char === '.' && substr($source, $i, 3) === '...') {
                // 展开运算符后的数组/对象/参数是完整操作数，不是点号后的成员名。
                $expect = true; $ambiguousSlash = false; $i += 2;
            }
            elseif ($char === '.') { $expect = false; $member = true; $ambiguousSlash = false; }
            elseif (str_contains('=[,:;?!~+-*/%<>&|^', $char)) { $expect = true; $ambiguousSlash = false; }
        }
        // 保存的是重读起点的状态，而非简单保存当前末尾状态；offset 仍指完整 source。
        $checkpoint = $anchor ?? compact('i', 'state', 'expect', 'escaped', 'characterClass', 'regexStart', 'templates', 'parentheses', 'pendingControl', 'member', 'ambiguousSlash', 'statementEnd', 'lineStart');
        $checkpoint['offset'] = $checkpoint['i']; unset($checkpoint['i']);
        if ($validate && $escaped) throw new \InvalidArgumentException('Template insertion after a JS escape is ambiguous');
        if ($validate && in_array($state, ['line', 'comment'], true)) throw new \InvalidArgumentException('Dynamic output in JS comments requires raw');
        if ($validate && $state === 'code' && !$expect) throw new \InvalidArgumentException('Dynamic JS identifiers or adjacent tokens require raw');
        $context = ['kind' => $state === 'code' ? 'js-value' : 'js-' . $state];
        if ($state === 'regex') $context['regexStart'] = $regexStart;
        return $validate ? $context : $context + ['expect' => $expect, 'escaped' => $escaped];
    }

    /**
     * 判断 CSS 前缀末尾的位置，支持样式正文和 style 属性中的声明列表。
     *
     * 保存引号、转义、注释、花括号深度、声明起点、url 起点及字符串起点；这些起点
     * 都相对于完整 source，而不是本次新增片段。返回 prefix 用于判断 URL 值是否
     * 已有静态部分。@import 的字符串按 URL 处理，普通值只接受受限字符集合。
     *
     * 断点保守重读尾部；url 与左圆括号之间允许长空白，尚未完成时从关键字前保留
     * 断点，不能因只留固定字节数而丢掉它。declarations 不能在同一断点流中切换。
     * @param string $source CSS 正文或实体解码后的 style 属性前缀。
     * @param bool $declarations true 将输入视为声明列表，初始花括号深度为 1。
     * @param array<string,mixed>|null $checkpoint 原地读写的 offset/quote/comment/位置等快照。
     * @return array{kind:string,prefix?:string,quote?:string} CSS 编码位置及可选 URL/引号信息。
     * @throws \InvalidArgumentException 末尾仍在未完成转义或注释中。
     */
    public static function css(string $source, bool $declarations = false, ?array &$checkpoint = null): array
    {
        $resume = $checkpoint ?? [];
        $quote = $resume['quote'] ?? '';
        $escaped = $resume['escaped'] ?? false;
        $comment = $resume['comment'] ?? false;
        $braces = $resume['braces'] ?? ($declarations ? 1 : 0);
        $start = $resume['start'] ?? 0;
        $url = $resume['url'] ?? null;
        $stringStart = $resume['stringStart'] ?? 0;
        $offset = $resume['offset'] ?? 0;
        $length = strlen($source);
        $boundary = max($offset, $length - 8);
        $anchor = null;
        for ($i = $offset; $i < $length; $i++) {
            if ($anchor === null && $i >= $boundary) $anchor = compact('i', 'quote', 'escaped', 'comment', 'braces', 'start', 'url', 'stringStart');
            $char = $source[$i]; $next = $source[$i + 1] ?? '';
            if ($comment) { if ($char === '*' && $next === '/') { $comment = false; $i++; } continue; }
            if ($escaped) { $escaped = false; continue; }
            if ($char === '\\') { $escaped = true; continue; }
            if ($quote !== '') { if ($char === $quote) $quote = ''; continue; }
            if ($char === '/' && $next === '*') { $comment = true; $i++; continue; }
            if ($char === '"' || $char === "'") { $quote = $char; $stringStart = $i + 1; continue; }
            if ($url === null && preg_match('/url\s*\(/Ai', $source, $match, 0, $i)) {
                if ($anchor === null && $i + strlen($match[0]) === $length) $anchor = compact('i', 'quote', 'escaped', 'comment', 'braces', 'start', 'url', 'stringStart');
                $i += strlen($match[0]) - 1; $url = $i + 1; continue;
            }
            // url 后的空白可任意长；遇到尚未完成的 url( 时从关键字前保留断点。
            if ($url === null && ($char === 'u' || $char === 'U') && preg_match('/url\s*$/Ai', $source, $match, 0, $i)) {
                if ($anchor === null) $anchor = compact('i', 'quote', 'escaped', 'comment', 'braces', 'start', 'url', 'stringStart');
                break;
            }
            if ($url !== null && $char === ')') { $url = null; continue; }
            if ($url !== null) continue;
            if ($char === '{') { $braces++; $start = $i + 1; }
            elseif ($char === '}') { $braces--; $start = $i + 1; }
            elseif ($char === ';') $start = $i + 1;
        }
        // absolute 位置（start/url/stringStart）随断点保存，恢复后仍对完整前缀取子串。
        $checkpoint = $anchor ?? compact('i', 'quote', 'escaped', 'comment', 'braces', 'start', 'url', 'stringStart');
        $checkpoint['offset'] = $checkpoint['i']; unset($checkpoint['i']);
        if ($escaped || $comment) throw new \InvalidArgumentException('Ambiguous CSS escape or comment insertion');
        $declaration = substr($source, $start);
        $import = (bool) preg_match('/^\s*@import\s+/i', $declaration);
        if ($quote !== '') return ['kind' => ($url !== null || $import) ? 'css-url-string' : 'css-string', 'prefix' => substr($source, $stringStart), 'quote' => $quote];
        if ($url !== null) return ['kind' => 'css-url', 'prefix' => ltrim(substr($source, $url))];
        if ($import) return ['kind' => 'css-import', 'prefix' => ''];
        return ['kind' => $braces > 0 && strpos($declaration, ':') !== false ? 'css-value' : 'css-ident'];
    }

    /**
     * 输出可放入 JS 值位置的 JSON，使用原生编码器并保护 HTML 特殊字符。
     *
     * 数组/对象作为结构保留；不额外开启未转义斜杠或行分隔符选项。JSON HEX 标记
     * 避免数据字符串直接产生标签字符，但不能替代 Context 的实际 HTML 状态跟踪。
     * @param mixed $value 原生 json_encode 支持的数据。
     * @return string 可作为完整 JS 值的 JSON 文本，字符串包含包围引号。
     * @throws \JsonException 非法 UTF-8、递归结构或其他 JSON 编码失败。
     * @throws \Throwable 用户 JsonSerializable 实现抛出的异常。
     */
    public static function json($value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * 把适用于文本位置的值转换为字符串，不对数组/普通对象作隐式 JSON 化。
     *
     * null、标量及 Stringable 可转换；Stringable 会执行用户代码，其副作用不是
     * 此纯转换函数的隔离职责，输出缓冲和实际状态由调用方管理。
     * @param mixed $value 待转换数据。
     * @return string 转换结果，尚未经过任何上下文编码。
     * @throws \InvalidArgumentException 类型不支持。
     * @throws \Throwable 用户 __toString 抛出的异常。
     */
    public static function string($value): string
    {
        if ($value === null || is_scalar($value) || $value instanceof \Stringable) return (string) $value;
        throw new \InvalidArgumentException('This template context requires a scalar or Stringable value');
    }

    /**
     * 按 javascript/css 返回的位置描述选择一种语言编码，不添加 HTML 属性层。
     *
     * js-value 输出括号包围的完整 JSON，隔离相邻运算符及数字 token；JS 字符串
     * 只取 JSON 字符串体，template 还编码反引号、
     * 美元和花括号，避免与静态 $ 拼成可执行插值；regex 按字符编码，不当作用户正则。
     * CSS URL 无前缀时检查变量协议；已有前缀时先做 URL 组成部分编码，二者再做
     * CSS 转义。拼接后的完整 URL 由 Context 在样式/属性结束时复核。
     * CSS 简单值只接受受限字符，其余结构应写在可信模板中或明确选择 raw。
     *
     * JSON script 的 json-value 直接输出 JSON，json-string 只输出双引号字符串体。
     * @param mixed $value 动态输入；js-value/json-value 接受任意可 JSON 化的结构。
     * @param array{kind:string,prefix?:string,quote?:string,regexStart?:bool} $context 扫描器产生的可信位置描述。
     * @return string JS/CSS 源码片段；事件/style 属性仍需再做 HTML 属性编码。
     * @throws \InvalidArgumentException 类型、UTF-8、URL 或 CSS 简单值不受支持。
     * @throws \JsonException JSON/JS 字符串编码失败。
     */
    public static function encode($value, array $context): string
    {
        $kind = $context['kind'];
        if ($kind === 'js-value') return '(' . self::json($value) . ')';
        if ($kind === 'json-value') return self::json($value);
        $value = self::string($value);
        if ($kind === 'json-string') return substr(self::json($value), 1, -1);
        if (str_starts_with($kind, 'js-')) {
            if ($kind === 'js-regex') {
                // 正则起始处的空数据不能把 / 与后续 / 或 * 拼成注释；字符类及已有
                // 正则内容后的空插值则保留为空，避免改变字符集合或已有量词的语义。
                if ($value === '' && ($context['regexStart'] ?? false)) return '(?:)';
                $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
                if ($characters === false) throw new \InvalidArgumentException('Invalid UTF-8 regex data');
                return implode('', array_map(static fn($char) => strlen($char) === 1 ? sprintf('\\x%02x', ord($char)) : substr(json_encode($char, JSON_THROW_ON_ERROR), 1, -1), $characters));
            }
            $string = substr(self::json($value), 1, -1);
            return $kind === 'js-template' ? str_replace(['`', '$', '{', '}'], ['\\u0060', '\\u0024', '\\u007b', '\\u007d'], $string) : $string;
        }
        if (in_array($kind, ['css-url', 'css-url-string', 'css-import'], true)) {
            if (($context['prefix'] ?? '') !== '') $value = rawurlencode($value);
            else self::url($value);
            $result = self::cssEscape($value, false, $kind === 'css-url');
            return $kind === 'css-import' ? '"' . $result . '"' : $result;
        }
        if ($kind === 'css-string') return self::cssEscape($value, false);
        if ($kind === 'css-ident') return self::cssEscape($value, true);
        if (!preg_match('/\A[-+#.%a-zA-Z0-9_\s]*\z/D', $value)) throw new \InvalidArgumentException('Unsafe CSS value; use a quoted CSS string or raw');
        return $value;
    }

    /**
     * 按 Unicode 码点做 CSS 十六进制转义，每个转义后补空格以截断后续十六进制字符。
     *
     * 标识符模式对首字符使用更严格的白名单；字符串模式允许普通空格，未加引号的
     * URL 则把空格与其他空白一样转义，避免产生无效 URL token。
     * 自行解码 UTF-8 字节，无需引入 mbstring；无效 UTF-8 直接拒绝。
     * @param string $value 待转义的 UTF-8 字符串。
     * @param bool $identifier true 作为选择器/属性等标识符片段，false 作为字符串/URL 内容。
     * @param bool $unquotedUrl true 时不保留 URL 中的普通空格；字符串中的空格仍可原样输出。
     * @return string CSS 源码内容，不包含包围引号。
     * @throws \InvalidArgumentException 输入不是有效 UTF-8。
     */
    private static function cssEscape(string $value, bool $identifier, bool $unquotedUrl = false): string
    {
        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($characters === false) throw new \InvalidArgumentException('Invalid UTF-8 CSS data');
        $output = '';
        foreach ($characters as $index => $char) {
            if (preg_match($identifier && $index === 0 ? '/^[A-Za-z_-]$/' : ($identifier || $unquotedUrl ? '/^[A-Za-z0-9_-]$/' : '/^[A-Za-z0-9 _-]$/'), $char)) { $output .= $char; continue; }
            $bytes = array_values(unpack('C*', $char));
            $codepoint = count($bytes) === 1 ? $bytes[0] : ($bytes[0] & (0x7f >> count($bytes)));
            if (count($bytes) > 1) foreach (array_slice($bytes, 1) as $byte) $codepoint = ($codepoint << 6) | ($byte & 0x3f);
            $output .= '\\' . dechex($codepoint) . ' ';
        }
        return $output;
    }

    /**
     * 检查 URL 的协议及容易导致浏览器解释差异的反斜杠，不修改或返回输入。
     *
     * 判定前解码 HTML 实体、移除控制/空白字节，识别混淆的危险协议；允许相对地址
     * 及配置在此实现中的常见协议。这不是域名白名单、URL 规范化或网络访问授权。
     * 调用方既要在合适的值位置调用，也要在标签/CSS 拼接结束后复核组合结果。
     * @param string $value 完整 URL 或待检查的 URL 数据。
     * @return void
     * @throws \InvalidArgumentException 协议不允许或包含反斜杠。
     */
    public static function url(string $value): void
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/[\x00-\x20\x7f]+/', '', trim($value));
        if (str_contains($value, '\\')) throw new \InvalidArgumentException('Backslashes are not allowed in template URLs');
        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $value, $match) && !in_array(strtolower($match[1]), ['http', 'https', 'mailto', 'tel', 'ftp'], true)) {
            throw new \InvalidArgumentException('Unsafe URL scheme');
        }
    }

    /**
     * 在动态 CSS 组合完成后，检查解码后的 URL 及不支持的危险构造。
     *
     * 先还原 CSS 转义并移除注释，再检查 expression/binding/behavior 和 url/@import。
     * 只针对当前实现支持的插值规则筛查，不是完整 CSS 语法解析器或可信样式白名单。
     * @param string $source 完整 style 正文或已做 HTML 实体解码的 style 属性值。
     * @return void
     * @throws \InvalidArgumentException 组合后包含不允许的构造、协议或反斜杠 URL。
     */
    public static function validateCss(string $source): void
    {
        $decoded = preg_replace_callback('/\\\\([0-9a-f]{1,6})(?:\s)?|\\\\([^\r\n])/i', static function ($match) {
            return isset($match[2]) && $match[2] !== '' ? $match[2] : html_entity_decode('&#' . hexdec($match[1]) . ';', ENT_NOQUOTES, 'UTF-8');
        }, $source);
        $decoded = preg_replace('~/\*.*?\*/~s', '', $decoded);
        if (preg_match('/expression\s*\(|(?:-moz-)?binding\s*:|behavior\s*:/i', $decoded)) throw new \InvalidArgumentException('Unsafe CSS construct');
        preg_match_all('~url\s*\(\s*(?:"([^"]*)"|\'([^\']*)\'|([^)]*))\s*\)|@import\s+(?:"([^"]*)"|\'([^\']*)\')~i', $decoded, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            foreach (array_slice($match, 1) as $url) if ($url !== '') { self::url(trim($url)); break; }
        }
    }
}
