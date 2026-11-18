<?php
namespace SlightPHP\Tpl;

/**
 * 保留原文顺序的模板词法切分器，供上下文编译器使用。
 *
 * 采用模板语法候选识别及引号/括号配对，减少普通 JS/CSS 花括号被误编译的情况。
 * 本类不构建 DOM；只在遇到 ${ 时探测原生 JS 模板字符串，输出转义位置仍由 Context 判断。
 * 与模板语法完全同形的原文无法总是消歧，应使用 literal 或不同分隔符。
 *
 * @internal 不提供表达式求值和不可信模板隔离。
 */
final class Lexer
{
    /**
     * 按源码顺序切分文本和模板标签，移除模板注释并展开显式 literal 块。
     *
     * text 保留字节内容；tag 仅包含去掉左右分隔符的主体，不在这里 trim 或执行。
     * $start 指向尚未提交的文本开头，$offset 是查找游标；跳过非标签候选时只移动
     * 查找游标，使跳过的原文最终仍随 text 输出。literal 不递归解析内部模板标签。
     *
     * @param string $source 完整模板源码。
     * @param string $left 非空左分隔符。
     * @param string $right 非空右分隔符。
     * @return array<array{0:'text'|'tag',1:string}> 有序 token 列表，相邻文本尽量合并。
     * @throws \InvalidArgumentException 分隔符为空，或候选标签、注释、literal 未闭合。
     */
    public static function tokenize(string $source, string $left, string $right): array
    {
        if ($left === '' || $right === '') throw new \InvalidArgumentException('Empty template delimiter');
        $tokens = [];
        $start = 0;
        $length = strlen($source);
        $candidatePattern = self::candidatePattern($right);
        $probe = null; $probeOffset = 0;
        for ($offset = 0; $offset < $length;) {
            $found = strpos($source, $left, $offset);
            if ($found === false) break;
            $bodyStart = $found + strlen($left);
            if ($left === '{' && $found > 0 && $source[$found - 1] === '$') {
                // 延迟建立源码探测器，普通 JS/CSS 花括号不承担上下文扫描成本。
                if ($probe === null) {
                    $probe = new Context();
                    foreach ($tokens as [$kind, $body]) {
                        if ($kind === 'text') self::probeLiteral($probe, $body);
                        else self::probeTag($probe, $body);
                    }
                    $probeOffset = $start;
                }
                self::probeLiteral($probe, substr($source, $probeOffset, $found - $probeOffset));
                $probeOffset = $found;
                if ($probe->javascriptTemplateSource()) {
                    $offset = $bodyStart; // 仅保留原生 JS 模板字符串中的 ${...}。
                    continue;
                }
            }
            if (substr($source, $bodyStart, 1) === '*') {
                $end = strpos($source, '*' . $right, $bodyStart + 1);
                if ($end === false) throw new \InvalidArgumentException('Unclosed template comment');
                self::text($tokens, substr($source, $start, $found - $start));
                if ($probe !== null) {
                    self::probeLiteral($probe, substr($source, $probeOffset, $found - $probeOffset));
                    $probeOffset = $end + 1 + strlen($right);
                }
                $offset = $start = $end + 1 + strlen($right);
                continue;
            }
            if (substr($source, $bodyStart, strlen('literal' . $right)) === 'literal' . $right) {
                $literalStart = $bodyStart + strlen('literal' . $right);
                $end = strpos($source, $left . '/literal' . $right, $literalStart);
                if ($end === false) throw new \InvalidArgumentException('Unclosed literal block');
                self::text($tokens, substr($source, $start, $found - $start) . substr($source, $literalStart, $end - $literalStart));
                if ($probe !== null) {
                    self::probeLiteral($probe, substr($source, $probeOffset, $found - $probeOffset) . substr($source, $literalStart, $end - $literalStart));
                    $probeOffset = $end + strlen($left . '/literal' . $right);
                }
                $offset = $start = $end + strlen($left . '/literal' . $right);
                continue;
            }
            if (!self::candidate($source, $bodyStart, $candidatePattern)) {
                $offset = $bodyStart;
                continue;
            }
            $end = self::end($source, $bodyStart, $right);
            if ($end === null) throw new \InvalidArgumentException('Unclosed template tag');
            $body = substr($source, $bodyStart, $end - $bodyStart);
            if (!self::isTag($body)) {
                $offset = $bodyStart;
                continue;
            }
            self::text($tokens, substr($source, $start, $found - $start));
            $tokens[] = ['tag', $body];
            if ($probe !== null) {
                self::probeLiteral($probe, substr($source, $probeOffset, $found - $probeOffset));
                self::probeTag($probe, $body);
                $probeOffset = $end + strlen($right);
            }
            $offset = $start = $end + strlen($right);
        }
        self::text($tokens, substr($source, $start));
        return $tokens;
    }

    /**
     * 探测只消费可信源码，临时缓冲阻止 Context 的字面量输出进入实际页面。
     * 每个片段最多提交一次；已跳过的原生 ${...} 仍作为原文提交，保留嵌套表达式状态。
     * @param Context $probe Lexer 独享的源码状态，不与渲染上下文共用。
     * @param string $text 下一段原文字节。
     */
    private static function probeLiteral(Context $probe, string $text): void
    {
        if ($text === '') return;
        ob_start();
        try { $probe->literal($text); }
        finally { ob_end_clean(); }
    }

    /**
     * 不执行模板代码。值标签用中性值占位，控制、赋值和 include 不模拟运行时效果。
     * 因此这是原生 JS 插值的源码消歧提示，绝不能用于选择安全编码器。
     * @param Context $probe Lexer 独享的源码状态。
     * @param string $body 不含分隔符的模板标签。
     */
    private static function probeTag(Context $probe, string $body): void
    {
        $body = trim($body);
        if (preg_match('~^/?(?:if|elseif|else|foreach|for|include|part)\b~i', $body)) return;
        if (preg_match('~^\$\w+\s*(?:=(?!=)|[+*/.%&|^\x2d]=|\?\?=|<<=|>>=)~', $body)) return;
        self::probeLiteral($probe, 'null');
    }

    /**
     * 向结果追加非空原文，并与相邻的 text 合并，减少编译结果中的输出调用。
     * @param array<array{string,string}> $tokens 原地修改的 token 列表。
     * @param string $text 待追加的原文字节。
     * @return void
     */
    private static function text(array &$tokens, string $text): void
    {
        if ($text === '') return;
        $last = count($tokens) - 1;
        if ($last >= 0 && $tokens[$last][0] === 'text') $tokens[$last][1] .= $text;
        else $tokens[] = ['text', $text];
    }

    /**
     * 快速筛掉不以常见模板语法开头的花括号，减少对 JS/CSS 原文的深度扫描。
     *
     * 这里只做宽松的候选判断；控制关键字大小写兼容，常量规则不整体忽略大小写，
     * 避免将普通小写 CSS/JS 标识符扩大为常量候选。最终还须 end/isTag 进一步检查。
     * @param string $source 完整源码，不为每个候选复制剩余后缀。
     * @param int $offset 左分隔符之后的字节偏移。
     * @param string $pattern 按实际右分隔符生成一次的候选正则。
     * @return bool 是否值得尝试读取一个模板标签。
     */
    private static function candidate(string $source, int $offset, string $pattern): bool
    {
        return (bool) preg_match($pattern, $source, $matches, 0, $offset);
    }

    /**
     * 常量和数字后可接实际右分隔符或表达式运算符，不写死 }、% 等分隔符字符。
     * 整个模板只构造一次；另外识别 PHP 的 true/false/null 字面量，其余常量仍限于
     * 大写命名，避免扩大普通 CSS/JS 标识符候选。
     * @param string $right 已校验非空的右分隔符，按正则字面量处理。
     * @return string 使用完整源码字节偏移匹配的候选正则。
     */
    private static function candidatePattern(string $right): string
    {
        $ending = '(?=(?:' . preg_quote($right, '~') . '|[\s+\x2d*/.%?!<>=&|^\[\x7e]))';
        return '~\G(?:\$|[\'\"]|(?i:/(?:if|foreach|for)\b|(?:if|elseif|else|foreach|for|include|part)\b)|[A-Za-z_\\\\][A-Za-z0-9_\\\\]*(?:::[A-Za-z_]\w*)?\s*\(|[A-Za-z_\\\\][A-Za-z0-9_\\\\]*::[A-Za-z_]\w*|[A-Za-z_]\w*\s+|(?:[A-Z_][A-Z0-9_]*|(?i:true|false|null))' . $ending . '|\d+' . $ending . ')~A';
    }

    /**
     * 区分常量/布尔/null 表达式与空格参数的函数简写，两个编译器共用。
     * 只判定首部形态，不在编译时读取常量值；后续 PHP 编译器负责表达式是否合法。
     * 已存在函数的空格调用（如 ROUND -1.2）仍优先按函数解析，保留历史调用方式。
     * @param string $body 去掉分隔符的标签主体。
     * @return bool 以大写常量或 true/false/null 开始，后接表达式运算符或结束。
     */
    public static function isConstantExpression(string $body): bool
    {
        if (!preg_match('~^(?:[A-Z_][A-Z0-9_]*|(?i:true|false|null))(?=\s*(?:$|[+\x2d*/.%?!<>=&|^\[\x7e]))~', $body, $match)) return false;
        $name = $match[0];
        if (preg_match('~\G\s+\S~A', $body, $unused, 0, strlen($name)) && (function_exists($name) || is_callable([Func::class, $name]))) return false;
        return true;
    }

    /**
     * 在完整候选主体上排除常见 JS 对象属性和原生控制块。
     *
     * 普通函数名候选检查内置 Func 方法、已存在的 PHP 函数及受支持的语言结构；
     * 命名空间/静态成员等表达式继续交给编译器，不以 tpl_function_* 扩展查找。
     * 该判断不是完整 JS/CSS 解析器。
     * @param string $body 已去掉分隔符的候选主体。
     * @return bool 是否交给后续模板编译器。
     */
    private static function isTag(string $body): bool
    {
        if (self::isConstantExpression($body)) return true;
        if (preg_match('~^[\'\"][^\'\"]*[\'\"]\s*:(?!:)~s', $body)) return false;
        if (preg_match('~^(?:if|elseif|foreach|for)\b~i', $body)) {
            // 原生 JS 的 if/for 函数体不应被当作一个模板控制标签。
            return !self::nativeControlBlock($body);
        }
        if (preg_match('~^(?:else|include|part)\b~i', $body)) return true;
        if (preg_match('~^([A-Za-z_]\w*)(?:\s*\(|\s+)~', $body, $match)) {
            $name = $match[1];
            return in_array(strtolower($name), ['isset', 'empty', 'array', 'list', 'match'], true) || function_exists($name) || is_callable([Func::class, $name]);
        }
        if (preg_match('~^\$[A-Za-z_]\w*\s*:(?!:)~', $body)) return false; // JS 对象属性。
        return true;
    }

    /**
     * 仅在字符串/注释之外识别 ) 后的控制块左花括号。条件中的 '){' 等只是数据，
     * 不能使合法模板控制标签被跳过。扫描不执行表达式，也不改变原始标签内容。
     */
    private static function nativeControlBlock(string $body): bool
    {
        $quote = ''; $afterParenthesis = false; $length = strlen($body);
        for ($i = 0; $i < $length; $i++) {
            $char = $body[$i];
            if ($quote !== '') {
                if ($char === '\\') $i++;
                elseif ($char === $quote) $quote = '';
                continue;
            }
            if ($char === '"' || $char === "'" || $char === '`') {
                $quote = $char; $afterParenthesis = false; continue;
            }
            if ($char === '/' && ($body[$i + 1] ?? '') === '*') {
                $end = strpos($body, '*/', $i + 2);
                if ($end === false) return false;
                $i = $end + 1; continue;
            }
            if ($char === '/' && ($body[$i + 1] ?? '') === '/') {
                $end = strcspn($body, "\r\n", $i + 2) + $i + 2;
                $i = $end - 1; continue;
            }
            if (str_contains(" \t\r\n\f\v", $char)) continue;
            if ($afterParenthesis && $char === '{') return true;
            $afterParenthesis = $char === ')';
        }
        return false;
    }

    /**
     * 找到候选标签的结束位置，跳过引号、反斜杠及成对括号内部的分隔符。
     *
     * 只有引号已结束且括号栈为空时，当前位置的右分隔符才有效。返回字节偏移，
     * 不包含右分隔符；括号不配对或源码结束仍未闭合时返回 null。
     * @param string $source 完整模板源码。
     * @param int $offset 主体第一个字节的位置。
     * @param string $right 非空右分隔符，可包含多个字节。
     * @return int|null 右分隔符起点，或无法完成配对。
     */
    private static function end(string $source, int $offset, string $right): ?int
    {
        $stack = [];
        $quote = '';
        $length = strlen($source);
        for ($i = $offset; $i < $length; $i++) {
            $char = $source[$i];
            if ($quote !== '') {
                if ($char === '\\') $i++;
                elseif ($char === $quote) $quote = '';
                continue;
            }
            if ($stack === [] && substr($source, $i, strlen($right)) === $right) return $i;
            if ($char === '"' || $char === "'" || $char === '`') { $quote = $char; continue; }
            if (str_contains('([{', $char)) $stack[] = $char;
            elseif (str_contains(')]}', $char)) {
                $expected = [')' => '(', ']' => '[', '}' => '{'][$char];
                if (array_pop($stack) !== $expected) return null;
            }
        }
        return null;
    }
}
