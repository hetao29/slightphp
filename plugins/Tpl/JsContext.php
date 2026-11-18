<?php
namespace SlightPHP\Tpl;

/**
 * 一个 script 或事件属性的 JS 计划执行流；运行时不逐字节解释 JS。
 *
 * 仅积累尚未消费的可信源码，在插值处按入口状态查找编译计划。已编码的数据用
 * 词法等价的短占位符推进，不把昵称、正文、JSON 对象内容等变成上下文缓存键。
 * 断点和 token 边界尾部跨 include/循环保留，新元素或新属性必须新建本对象。
 * @internal 与实际 HTML 状态分开；HTML 结构仍由 Context 跟踪实际输出。
 */
final class JsContext
{
    /** @var JsPlanCache 同一渲染流共享的计划缓存；clone 时保留共享关系。 */
    private JsPlanCache $cache;
    /** @var array<string,mixed> 已消费前缀的 token 边界状态，offset 始终归零。 */
    private array $checkpoint = [];
    /** @var string 必须从断点重读的尾部，可能包含未结束的长 token。 */
    private string $tail = '';
    /** @var array<string> 尚未消费的源码块；长静态块保留引用，避免无后续插值时白复制。 */
    private array $pending = [];
    /** @var array<string,mixed>|null 当前边界的编译结果；追加非空内容后失效。 */
    private ?array $context = null;

    /** @param JsPlanCache $cache 当前输出流共享的 JS 计划集。 */
    public function __construct(JsPlanCache $cache) { $this->cache = $cache; }

    /**
     * 追加可信 JS 源码；事件属性增量须由调用者先做 HTML 实体解码。
     * @param string $source 需要追加的源码或编码值对应的词法占位符。
     * @return void
     */
    public function literal(string $source): void
    {
        if ($source === '') return;
        $last = count($this->pending) - 1;
        if ($last >= 0 && strlen($source) < 256 && strlen($this->pending[$last]) < 256) $this->pending[$last] .= $source;
        else $this->pending[] = $source;
        $this->context = null;
    }

    /**
     * 查表取得当前编码器；错误计划仍拒绝输出，不降级为 raw。
     * @return array{kind:string,regexStart?:bool} 当前动态值的 JS 编码位置及正则起始标记。
     * @throws \InvalidArgumentException 位置不支持或源码存在歧义。
     */
    public function context(): array
    {
        if ($this->context === null) {
            $plan = $this->cache->get($this->checkpoint, $this->tail . implode('', $this->pending));
            if (isset($plan['error'])) throw new \InvalidArgumentException($plan['error']);
            $this->checkpoint = $plan['checkpoint'];
            $this->tail = base64_decode($plan['tail']);
            $this->pending = [];
            $this->context = $plan;
        }
        $plan = $this->context;
        if ($plan['escaped']) throw new \InvalidArgumentException('Template insertion after a JS escape is ambiguous');
        if (in_array($plan['kind'], ['js-line', 'js-comment'], true)) throw new \InvalidArgumentException('Dynamic output in JS comments requires raw');
        if ($plan['kind'] === 'js-value' && !$plan['expect']) throw new \InvalidArgumentException('Dynamic JS identifiers or adjacent tokens require raw');
        return $plan['kind'] === 'js-regex' ? ['kind' => $plan['kind'], 'regexStart' => $plan['regexStart']] : ['kind' => $plan['kind']];
    }

    /**
     * 完整 JS 值始终由编码器包在括号中，因此统一用 (0) 描述其外部词法状态，
     * 不让数据类型、数值符号或内容影响后续计划。JSON 数据块不加括号，单独保留其
     * 外部 token 形态。字符串/正则中的空内容仍保留为空，
     * 覆盖跨空值形成 ${...}、// 或 /* 等新词法边界的情况。
     * @param string $encoded 完成 JS 编码、未加 HTML 属性层的结果。
     * @param array{kind:string,regexStart?:bool} $context 本次已验证的编码位置。
     * @return void
     */
    public function value(string $encoded, array $context): void
    {
        if ($context['kind'] === 'json-value') {
            // JSON 不能加表达式括号，保留外部 token 形态，避免影响下一个插值的判定。
            $first = $encoded[0] ?? '';
            if ($first === '"') $source = '""';
            elseif ($first === '{') $source = '{}';
            elseif ($first === '[') $source = '[]';
            elseif ($first === '-' || ($first >= '0' && $first <= '9')) $source = preg_replace('/[0-9]+/', '0', $encoded);
            else $source = 'null';
            $this->literal($source); return;
        }
        if ($context['kind'] !== 'js-value') {
            $this->literal($encoded === '' ? '' : 'x');
            return;
        }
        $this->literal('(0)');
    }
}
