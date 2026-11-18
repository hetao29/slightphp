<?php
namespace SlightPHP\Tpl;

/**
 * 自动模式的 PHP 代码生成器，输入来自 Lexer 的有序 token。
 *
 * 字面量生成普通状态机调用或带入口守卫的静态块输出；值生成通用、HTML 文本或
 * 属性编码调用。所有表达式在运行时求值，编译结果不保存请求数据或最终 HTML。
 *
 * 候选上下文只用占位值推进，不执行真实请求数据，也不假设分支条件之间的关系。
 * 分支保存独立入口，循环考虑零次/一次路径，include/raw 后放弃未知入口的静态预测。
 * JS 编码及状态推进保存在编译文件的计划表中，动态新入口另编译上下文变体。
 * 不得因为编译期预测成功而删除守卫，也不得在分析时执行用户模板表达式。
 *
 * @internal 这是可信模板的编译器，不是 PHP 沙箱或完整语法解析器。
 */
final class Compiler
{
    /** JS 词法判定/计划语义变更时递增，同时使主模板与上下文变体缓存失效。 */
    public const JS_VERSION = 5;

    /**
     * 将 JS 前缀的词法推进编译成编码位置及下一断点，供模板编译和上下文变体使用。
     * 渲染命中计划时不调用本方法，也不调用 CodeContext 的 JS 扫描器。
     * @param string $source 上一断点的尾部加新静态源码/规范化的数据占位符。
     * @param array<string,mixed> $checkpoint 从 source 第 0 字节恢复的词法状态。
     * @return array<string,mixed> 编码位置、插值资格和下一断点；尾部按 base64 保存字节。
     */
    public static function compileJavascriptPlan(string $source, array $checkpoint): array
    {
        try {
            $context = CodeContext::javascript($source, $checkpoint, false);
            $tail = substr($source, $checkpoint['offset']);
            $checkpoint['offset'] = 0;
            return $context + ['checkpoint' => $checkpoint, 'tail' => base64_encode($tail)];
        } catch (\InvalidArgumentException $error) {
            return ['error' => $error->getMessage()];
        }
    }

    /**
     * 生成将在 Tpl 实例作用域 include 的 PHP 文本。
     *
     * 控制标签转换为 PHP 控制结构，闭合栈用于匹配 if/foreach/for；赋值保留为语句，
     * 普通表达式经 evaluateContext 求值后编码输出，修饰链交给 ModifierValue::apply。
     *
     * @param array<array{0:'text'|'tag',1:string}> $tokens Lexer 返回的文本及标签主体。
     * @return string 包含 PHP 标签的编译文本，不包含 fetch 追加的缓存文件头。
     * @throws \InvalidArgumentException 控制结构、修饰器或表达式结构不受支持。
     */
    public static function compile(array $tokens): string
    {
        // 闭合栈校验结构；预测失败置 null，只影响快路径选择，不隐藏运行时错误。
        $tokens = array_values($tokens); $blocks = self::htmlBlocks($tokens); $blockEnd = -1;
        $output = ''; $stack = []; $frames = []; $javascriptPlanCache = new JsPlanCache(); $predictions = [new Context($javascriptPlanCache)];
        foreach ($tokens as $index => [$kind, $body]) {
            if ($blockEnd >= 0 && $index > $blockEnd) { $output .= '<?php } ?>'; $blockEnd = -1; }
            if (isset($blocks[$index])) {
                $nonText = $predictions !== [];
                foreach ($predictions as $prediction) if ($prediction === null || $prediction->isText()) { $nonText = false; break; }
                if (!$nonText) {
                    $blockEnd = $blocks[$index]['end'];
                    $output .= $blocks[$index]['code'];
                }
            }
            if ($kind === 'text') {
                $plan = self::literalPlan($body);
                $output .= $plan === []
                    ? '<?php $this->writeContextLiteral(' . var_export($body, true) . '); ?>'
                    : '<?php $this->writeContextLiteralPlan(' . var_export($body, true) . ',' . var_export($plan, true) . '); ?>';
                foreach ($predictions as &$prediction) self::predictLiteral($prediction, $body);
                unset($prediction);
                continue;
            }
            $body = preg_replace_callback('~^/?(?:if|elseif|else|foreach|for)\b~i', static fn($m) => strtolower($m[0]), trim($body));
            if (preg_match('~^/(if|foreach|for)$~', $body, $match)) {
                if (array_pop($stack) !== $match[1]) throw new \InvalidArgumentException('Mismatched template control tag: ' . $body);
                $frame = array_pop($frames);
                $predictions = array_merge($frame['branches'], $predictions, $frame['else'] ? [] : $frame['entry']);
                $predictions = array_slice($predictions, 0, 16);
                $output .= '<?php } ?>'; continue;
            }
            if ($body === 'else' || preg_match('/^elseif\b(.*)$/s', $body, $match)) {
                if (end($stack) !== 'if') throw new \InvalidArgumentException('else/elseif requires if');
                $index = count($frames) - 1;
                $frames[$index]['branches'] = array_merge($frames[$index]['branches'], $predictions);
                $frames[$index]['else'] = $body === 'else';
                $predictions = self::clonePredictions($frames[$index]['entry']);
                $output .= $body === 'else' ? '<?php } else { ?>' : '<?php } elseif (' . self::evaluate(self::expression(trim($match[1]))) . ') { ?>';
                continue;
            }
            if (preg_match('/^(if|foreach|for)\b(.*)$/s', $body, $match)) {
                $control = $match[1]; $expression = trim($match[2]);
                if ($control !== 'if' && str_starts_with($expression, '(') && str_ends_with($expression, ')')) $expression = substr($expression, 1, -1);
                if ($control === 'foreach') {
                    $parts = self::split($expression, T_AS);
                    if (count($parts) !== 2) throw new \InvalidArgumentException('foreach requires a collection and as target');
                    $code = self::evaluate(self::expression($parts[0])) . ' as ' . self::expression($parts[1]);
                } elseif ($control === 'for') {
                    $parts = self::split($expression, ';');
                    if (count($parts) !== 3) throw new \InvalidArgumentException('for requires three clauses');
                    $code = implode(';', array_map(static fn($part) => implode(',', array_map(static fn($item) => trim($item) === '' ? '' : self::evaluate(self::expression($item)), self::split($part, ','))), $parts));
                } else $code = self::evaluate(self::expression($expression));
                $frames[] = ['entry' => self::clonePredictions($predictions), 'branches' => [], 'else' => false];
                $stack[] = $control; $output .= '<?php ' . $control . '(' . $code . ') { ?>'; continue;
            }
            if (preg_match('/^(include|part)\s*(?:\((.*)\)|(\s+.*))$/is', $body, $match)) {
                $expression = self::expression(trim($match[2] !== '' ? $match[2] : $match[3]));
                $predictions = []; // include/part 的实际输出可能引入此前无法静态确定的入口。
                $output .= '<?php $this->' . (strtolower($match[1]) === 'include' ? 'fetch' : 'writeContextPart') . '(' . self::evaluate($expression) . '); ?>'; continue;
            }
            $parts = self::split($body, '|'); $body = array_shift($parts); $raw = false;
            $filters = [];
            foreach ($parts as $part) {
                $arguments = self::split($part, ':');
                $name = trim(array_shift($arguments));
                if (!preg_match('/^[A-Za-z_]\w*$/D', $name)) throw new \InvalidArgumentException('Invalid template modifier: ' . $name);
                $raw = strtolower($name) === 'raw';
                $filters[] = '[' . var_export($name, true) . ',[' . implode(',', array_map(static fn($argument) => self::expression($argument), $arguments)) . ']]';
            }
            // 兼容函数的空格参数写法；复杂表达式请使用括号和逗号。
            if (!Lexer::isConstantExpression($body) && preg_match('/^([a-zA-Z_]\w*)\s+(.+)$/s', $body, $match)) {
                $name = $match[1];
                $parameters = self::arguments($match[2]);
                $body = $name . '(' . implode(',', $parameters) . ')';
            }
            $assignment = self::assignment($body);
            $expression = self::expression($body);
            if ($filters !== []) $expression = '\\SlightPHP\\Tpl\\ModifierValue::apply(' . $expression . ',[' . implode(',', $filters) . '])';
            if ($assignment && $filters === []) {
                $output .= '<?php ' . self::evaluate($expression) . '; ?>';
            } else {
                // PHP 会先计算方法实参，再执行入口守卫；表达式自身可能改变 Context。
                $hint = [];
                if (!$raw) foreach ($predictions as $prediction) {
                    // 各路径都进行 JS 编译，即使最终只选一个带运行时守卫的输出候选。
                    $candidate = $prediction !== null ? $prediction->valueHint() : [];
                    if ($hint === [] && $candidate !== []) $hint = $candidate;
                }
                if (($hint['kind'] ?? '') === 'html') $output .= '<?php $this->writeContextHtml(' . self::evaluate($expression) . '); ?>';
                elseif (($hint['kind'] ?? '') === 'javascript') $output .= '<?php $this->writeContextJavascript(' . self::evaluate($expression) . ',' . var_export($hint['context'], true) . '); ?>';
                elseif (($hint['kind'] ?? '') === 'attribute') {
                    unset($hint['kind']);
                    $output .= '<?php $this->writeContextAttribute(' . self::evaluate($expression) . ',' . var_export($hint, true) . '); ?>';
                } else $output .= '<?php $this->writeContextValue(' . self::evaluate($expression) . ',' . ($raw ? 'true' : 'false') . '); ?>';
                if ($raw) $predictions = [];
                else foreach ($predictions as &$prediction) self::predictValue($prediction);
                unset($prediction);
            }
        }
        if ($blockEnd >= 0) $output .= '<?php } ?>';
        if ($stack !== []) throw new \InvalidArgumentException('Unclosed template control tag');
        $plans = $javascriptPlanCache->export();
        return ($plans === [] ? '' : '<?php $this->registerJavascriptPlans(' . var_export($plans, true) . '); ?>') . $output;
    }

    /**
     * 把连续的字面量及纯变量/常量键查找作为候选块；控制、函数、修饰器和赋值均切断。
     * 原代码仍逐 token 编译在 else 分支中，候选上下文/JS 计划不因直接输出路径而丢失。
     */
    private static function htmlBlocks(array $tokens): array
    {
        $blocks = []; $start = 0; $count = count($tokens);
        for ($i = 0; $i <= $count; $i++) {
            if ($i < $count && ($tokens[$i][0] === 'text' || self::lookupPath($tokens[$i][1]) !== null)) continue;
            if ($i > $start) {
                $code = self::htmlBlock(array_slice($tokens, $start, $i - $start));
                if ($code !== null) $blocks[$start] = ['end' => $i - 1, 'code' => $code];
            }
            $start = $i + 1;
        }
        return $blocks;
    }

    /** 只接受变量及整数/不插值字符串常量键，路径本身不会调用用户函数。 */
    private static function lookupPath(string $source): ?string
    {
        $tokens = token_get_all('<?php ' . trim($source)); array_shift($tokens);
        $tokens = array_values(array_filter($tokens, static fn($t) => !is_array($t) || !in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)));
        if (!isset($tokens[0]) || !is_array($tokens[0]) || $tokens[0][0] !== T_VARIABLE) return null;
        $root = var_export(substr($tokens[0][1], 1), true); $keys = [];
        for ($i = 1, $count = count($tokens); $i < $count;) {
            if ($tokens[$i++] !== '[') return null;
            $negative = ($tokens[$i] ?? null) === '-';
            if ($negative) $i++;
            $key = $tokens[$i++] ?? null;
            if (!is_array($key) || !in_array($key[0], [T_LNUMBER, T_CONSTANT_ENCAPSED_STRING], true) || ($negative && $key[0] !== T_LNUMBER)) return null;
            if (($tokens[$i++] ?? null) !== ']') return null;
            $keys[] = ($negative ? '-' : '') . $key[1];
        }
        return '[' . $root . ',[' . implode(',', $keys) . ']]';
    }

    /**
     * 编译完整的普通 HTML 块为原生取值、编码与 echo。只处理可证明无回调的只读路径，
     * 运行时还要求路径节点为原生数组、叶子为标量/null，且实际入口为 HTML 文本。
     * URL 仅接受不含其它变量的固定 / 前缀，并保留拼接后的协议检查；其它敏感位置回退。
     * 每块限制源码大小，避免给大型静态文档额外生成重复代码。
     */
    private static function htmlBlock(array $tokens): ?string
    {
        $bytes = 0; $hasValue = false; $source = '';
        foreach ($tokens as [$kind, $body]) { $bytes += strlen($body); $hasValue = $hasValue || $kind === 'tag'; }
        if (!$hasValue || $bytes > 65536) return null;
        foreach ($tokens as [$kind, $body]) $source .= $body;
        $prefix = '__slight_html_' . substr(hash('sha256', $source), 0, 16) . '_';
        while (str_contains($source, $prefix)) $prefix .= '_';
        $paths = []; $pathIds = []; $encoded = []; $encodingIds = []; $markers = []; $context = new Context();
        ob_start();
        try {
            foreach ($tokens as [$kind, $body]) {
                if ($kind === 'text') { $context->literal($body); continue; }
                $path = self::lookupPath($body); if ($path === null) return null;
                $hint = $context->valueHint(); $encoding = 'html'; $attribute = null;
                if (($hint['kind'] ?? '') === 'attribute') {
                    [$attribute, $before, $quote] = $hint['attribute'];
                    if ($quote === '' || str_starts_with($attribute, 'on') || in_array($attribute, ['style', 'srcset', 'imagesrcset', 'content', 'srcdoc', 'xmlns', 'http-equiv', 'charset'], true)) return null;
                    if ($hint['url'] ?? false) {
                        if (!str_starts_with($before, '/') || str_contains($before, $prefix)) return null;
                        $encoding = 'url';
                    }
                } elseif (($hint['kind'] ?? '') !== 'html') return null;
                if (!isset($pathIds[$path])) { $pathIds[$path] = count($paths); $paths[] = $path; }
                $id = $pathIds[$path]; $key = $id . ':' . $encoding;
                if (!isset($encodingIds[$key])) {
                    $encodingIds[$key] = count($encoded);
                    $value = '(string)$__tpl_html_values[' . $id . ']';
                    // rawurlencode 的结果只含 URL 安全 ASCII/%HH，无需再做一次 HTML 编码。
                    $encoded[] = $encoding === 'url' ? 'rawurlencode(' . $value . ')'
                        : 'htmlspecialchars(' . $value . ',ENT_QUOTES|ENT_SUBSTITUTE,\'UTF-8\')';
                }
                $marker = $prefix . count($markers) . '__';
                $markers[$marker] = ['id' => $encodingIds[$key], 'attribute' => $attribute, 'url' => $encoding === 'url'];
                $context->value($marker);
            }
            $context->finish(); $html = ob_get_contents();
        } catch (\Throwable $error) { return null; }
        finally { ob_end_clean(); }
        $pattern = '~(' . preg_quote($prefix, '~') . '[0-9]+__)~';
        $pieces = self::htmlPieces($html);
        if ($pieces === null) return null;
        $code = ''; $echo = [];
        foreach ($pieces as $piece) {
            $checks = [];
            if ($piece[0] === '<') {
                if (!preg_match('~\A<(/?)([a-zA-Z][a-zA-Z0-9-]*)(?=[\t\n\f\r />])~', $piece, $head)) return null;
                if (in_array(strtolower($head[2]), ['script', 'style', 'title', 'textarea', 'svg', 'math', 'xmp', 'iframe', 'noembed', 'noframes', 'plaintext', 'noscript', 'meta'], true)) return null;
                preg_match_all('~([^\s=<>/]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))~', $piece, $attributes, PREG_SET_ORDER);
                $seen = [];
                foreach ($attributes as $attr) {
                    $name = strtolower($attr[1]); $raw = $attr[2] !== '' ? $attr[2] : (($attr[3] ?? '') !== '' ? $attr[3] : ($attr[4] ?? ''));
                    if (!str_contains($raw, $prefix)) continue;
                    preg_match_all($pattern, $raw, $matches); $url = false;
                    foreach ($matches[0] as $marker) {
                        if ($markers[$marker]['attribute'] !== $name) return null;
                        $url = $url || $markers[$marker]['url'];
                        $seen[$marker] = true;
                    }
                    if ($url) $checks[] = self::htmlExpression($raw, $pattern, $markers);
                }
                preg_match_all($pattern, $piece, $matches);
                foreach ($matches[0] as $marker) if (!isset($seen[$marker])) return null;
            } else {
                if (str_contains($piece, '<')) return null;
                preg_match_all($pattern, $piece, $matches);
                foreach ($matches[0] as $marker) if ($markers[$marker]['attribute'] !== null) return null;
            }
            if ($checks !== []) {
                if ($echo !== []) { $code .= 'echo ' . implode(',', $echo) . ';'; $echo = []; }
                foreach ($checks as $check) $code .= '\\SlightPHP\\Tpl\\CodeContext::url(html_entity_decode(' . $check . ',ENT_QUOTES|ENT_HTML5,\'UTF-8\'));';
            }
            $echo[] = self::htmlExpression($piece, $pattern, $markers);
        }
        if ($echo !== []) $code .= 'echo ' . implode(',', $echo) . ';';
        return '<?php if (($__tpl_html_values=$this->contextValues([' . implode(',', $paths) . ']))!==null && $this->outputContext->beginHtml()) {'
            . '$__tpl_html_encoded=[' . implode(',', $encoded) . '];' . $code . '} else { ?>';
    }

    /** 单次前进切分完整 HTML 标签，忽略属性引号内的 >；不对每个 < 重扫剩余全文。 */
    private static function htmlPieces(string $html): ?array
    {
        $pieces = []; $offset = 0; $length = strlen($html);
        while (($start = strpos($html, '<', $offset)) !== false) {
            if ($start > $offset) $pieces[] = substr($html, $offset, $start - $offset);
            $quote = ''; $end = null;
            for ($i = $start + 1; $i < $length; $i++) {
                $char = $html[$i];
                if ($quote !== '') { if ($char === $quote) $quote = ''; continue; }
                if ($char === '"' || $char === "'") $quote = $char;
                elseif ($char === '>') { $end = $i; break; }
                elseif ($char === '<') return null;
            }
            if ($end === null) return null;
            $pieces[] = substr($html, $start, $end - $start + 1); $offset = $end + 1;
        }
        if ($offset < $length) $pieces[] = substr($html, $offset);
        return $pieces;
    }

    /** 将编译期标记替换为编码后的局部变量引用；真实请求值不会参与源码替换。 */
    private static function htmlExpression(string $source, string $pattern, array $markers): string
    {
        $parts = preg_split($pattern, $source, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY); $code = [];
        foreach ($parts as $part) $code[] = isset($markers[$part]) ? '$__tpl_html_encoded[' . $markers[$part]['id'] . ']' : var_export($part, true);
        return $code === [] ? "''" : implode(' . ', $code);
    }

    /**
     * 为 if/elseif/else 和循环的零次路径保存入口，防止互相污染扫描状态。
     * 编译期最多展开 16 个候选路径；复杂动态入口仍由独立上下文变体编译并缓存。
     * @param array<Context|null> $predictions 候选输出状态。
     * @return array<Context|null> 共享计划表、独立流状态的副本。
     */
    private static function clonePredictions(array $predictions): array
    {
        return array_map(static fn($prediction) => $prediction === null ? null : clone $prediction, $predictions);
    }

    /**
     * 编译静态 HTML 片段的文本/标签入口计划；运行时仍检查实际入口。
     *
     * 这不要求 div 等 DOM 元素全部闭合，也不是 HTML 安全清洗；输入是可信原文。
     * 预演输出只进入临时缓冲，绝不写入页面。实际入口由 writeContextLiteralPlan 再检查。
     * 紧邻插值的 "> 或 '> 单独作为闭合指令：保留实际标签、实际属性数据和 closeTag
     * 的组合校验，不把预测占位值写进计划。其后片段只有回到 HTML 文本时才能执行计划。
     * @param string $text 一个 text token 的原文。
     * @return array 静态计划；无法确定的 body/comment/外来元素入口走常规 literal。
     */
    private static function literalPlan(string $text): array
    {
        if (strlen($text) >= 2 && ($text[0] === '"' || $text[0] === "'") && $text[1] === '>') {
            $tail = substr($text, 2);
            return ['mode' => 'close', 'quote' => $text[0], 'after' => self::textLiteralPlan($tail)];
        }
        return self::textLiteralPlan($text);
    }

    /** 在干净的 HTML 文本入口预演一次，保存静态输出及未完成标签，不保存动态数据。 */
    private static function textLiteralPlan(string $text): array
    {
        ob_start();
        try {
            $context = new Context();
            $context->literal($text);
            $plan = $context->literalHint();
            if (($plan['mode'] ?? '') === 'open') $plan['output'] = ob_get_contents();
            return $plan;
        } catch (\Throwable $error) {
            return [];
        } finally { ob_end_clean(); }
    }

    /**
     * 把原文推进候选上下文，供之后的值输出选择计划。
     *
     * 预测异常仅取消后续预测，不让占位数据导致有效模板在编译期被错误拒绝；
     * 真实渲染仍执行自己的上下文检查。此缓冲也防止预演文字泄露到当前输出流。
     * @param Context|null $context 原地更新；无法继续预测时置 null。
     * @param string $text 模板中的可信字面量。
     * @return void
     */
    private static function predictLiteral(?Context &$context, string $text): void
    {
        if ($context === null) return;
        ob_start();
        try { $context->literal($text); }
        catch (\Throwable $error) { $context = null; }
        finally { ob_end_clean(); }
    }

    /**
     * 用固定占位值推进预测，不读取实际模板变量，也不调用用户函数。
     *
     * 例如占位值与静态后缀可能组合成不允许的 URL，此时只放弃预测；实际值在
     * 运行时可能有效，不能把这一预测失败当作模板编译错误。
     * @param Context|null $context 原地更新；无法预测时置 null。
     * @return void
     */
    private static function predictValue(?Context &$context): void
    {
        if ($context === null) return;
        ob_start();
        try { $context->value('x'); }
        catch (\Throwable $error) { $context = null; }
        finally { ob_end_clean(); }
    }

    /**
     * 为表达式生成统一的有输出检测的求值调用，不在编译时执行它。
     * @param string $expression 已映射模板变量的 PHP 表达式。
     * @return string 调用 Tpl::evaluateContext 的 PHP 代码片段。
     */
    private static function evaluate(string $expression): string
    {
        return '$this->evaluateContext(fn() => (' . $expression . '))';
    }

    /**
     * 判断表达式顶层是否包含赋值或复合赋值，区分不输出的赋值语句。
     *
     * token 级判断不会把 ==、=== 或函数参数中的赋值当作顶层赋值。
     * @param string $source 尚未映射模板变量的表达式。
     * @return bool 是否按赋值语句生成代码。
     */
    private static function assignment(string $source): bool
    {
        $tokens = token_get_all('<?php ' . $source); array_shift($tokens);
        $depth = 0;
        foreach ($tokens as $token) {
            $type = is_array($token) ? $token[0] : $token;
            if ($depth === 0 && in_array($type, ['=', T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_CONCAT_EQUAL, T_MOD_EQUAL, T_AND_EQUAL, T_OR_EQUAL, T_XOR_EQUAL, T_SL_EQUAL, T_SR_EQUAL, T_POW_EQUAL, T_COALESCE_EQUAL], true)) return true;
            if (is_string($token) && str_contains('([{', $token)) $depth++;
            elseif (is_string($token) && str_contains(')]}', $token)) $depth--;
        }
        return false;
    }

    /**
     * 将模板变量 token 映射到当前 Tpl 实例的变量表，并解析首部内置函数。
     *
     * 字符串常量保持原样；拒绝多条顶层语句、显式输出/包含、退出、eval 和 PHP
     * 字符串插值等不受支持的结构。此处限制语法形态，不限制普通函数的业务权限。
     * @param string $source 原始表达式或控制结构的一部分。
     * @return string 可放入编译结果的 PHP 表达式，尚未求值。
     * @throws \InvalidArgumentException 出现不支持的 token 或多条顶层语句。
     */
    private static function expression(string $source): string
    {
        $tokens = token_get_all('<?php ' . trim($source)); array_shift($tokens);
        $result = '';
        foreach ($tokens as $index => $token) {
            if (!is_array($token)) { $result .= $token; continue; }
            [$type, $text] = $token;
            if (in_array($type, [T_OPEN_TAG, T_CLOSE_TAG, T_ECHO, T_PRINT, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_EXIT, T_EVAL, T_ENCAPSED_AND_WHITESPACE, T_START_HEREDOC, T_INLINE_HTML], true)) throw new \InvalidArgumentException('Unsupported template expression; use a return-value function');
            if ($type === T_VARIABLE) $text = '$this->_tpl_vars[' . var_export(substr($text, 1), true) . ']';
            elseif ($type === T_STRING && $index === 0 && is_callable([Func::class, $text])) $text = '\\SlightPHP\\Tpl\\Func::' . $text;
            $result .= $text;
        }
        if (count(self::split($result, ';')) !== 1) throw new \InvalidArgumentException('Multiple template statements are unsupported');
        return $result;
    }

    /**
     * 按顶层 token 分隔符拆分源码，跳过字符串以及圆/方/花括号内部的同类符号。
     *
     * 使用 token_get_all，故 ||、== 等复合运算符不会被误当作单个 | 或 =；
     * T_AS 用于 foreach，分号用于 for，逗号用于其初始化/迭代表达式列表。
     * @param string $source 待拆分的 PHP 片段。
     * @param int|string $separator PHP token ID 或单字符 token。
     * @return string[] 按出现顺序保留的片段，允许空片段（如 for 的空条件）。
     */
    private static function split(string $source, $separator): array
    {
        $tokens = token_get_all('<?php ' . $source); array_shift($tokens);
        $parts = ['']; $depth = 0;
        foreach ($tokens as $token) {
            $type = is_array($token) ? $token[0] : $token;
            $text = is_array($token) ? $token[1] : $token;
            if ($type === $separator && $depth === 0) { $parts[] = ''; continue; }
            if (is_string($token) && str_contains('([{', $token)) $depth++;
            elseif (is_string($token) && str_contains(')]}', $token)) $depth--;
            $parts[count($parts) - 1] .= $text;
        }
        return $parts;
    }

    /**
     * 把兼容空格调用写法的顶层参数分开，保留引号、数组和括号内的空白。
     *
     * 用于 {tostring $value} 等简写；带运算符的复杂参数应使用函数括号/逗号写法，
     * 不依赖空格来猜测完整 PHP 表达式的参数边界。
     * @param string $source 函数名之后的参数源码。
     * @return string[] 顺序不变的非空参数片段。
     */
    private static function arguments(string $source): array
    {
        $tokens = token_get_all('<?php ' . $source); array_shift($tokens);
        $parts = ['']; $depth = 0;
        foreach ($tokens as $token) {
            $text = is_array($token) ? $token[1] : $token;
            if (is_array($token) && $token[0] === T_WHITESPACE && $depth === 0) { if (end($parts) !== '') $parts[] = ''; continue; }
            if (is_string($token) && str_contains('([{', $token)) $depth++;
            elseif (is_string($token) && str_contains(')]}', $token)) $depth--;
            $parts[count($parts) - 1] .= $text;
        }
        return array_values(array_filter($parts, static fn($part) => $part !== ''));
    }
}
