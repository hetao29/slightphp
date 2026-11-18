<?php
namespace SlightPHP\Tpl;

/**
 * 自动模式的修饰结果及输出意图，不是向任意上下文通用的“安全字符串”。
 *
 * 最后一个 HTML escape 可以交由实际上下文统一编码，避免 HTML 双重编码或把
 * HTML 编码误当 JS 编码。只有最后一个 raw 明确允许按可信原文输出最终结果。
 * @internal 由编译文件调用 apply 产生，只在一次值输出期间使用，不写入编译计划。
 */
final class ModifierValue
{
    /** @var mixed 修饰链的实际结果，用于普通自动编码或明确的 raw 输出。 */
    public $value;
    /** @var mixed 最后一次显式 escape 之前的数据，供实际上下文编码器复用。 */
    public $beforeEscape;
    /** @var string|null 尚未被后续变换失效的 html/url 编码意图。 */
    public ?string $escape;
    /** @var bool 最后一个修饰器是否为 raw；中间 raw 不放弃后续自动编码。 */
    public bool $raw;

    public function __construct($value, bool $raw, ?string $escape = null, $beforeEscape = null)
    {
        $this->value = $value;
        $this->raw = $raw;
        $this->escape = $escape;
        $this->beforeEscape = $beforeEscape;
    }

    /**
     * 执行编译好的修饰链，参数及数据在运行时求值，编译期间不执行修饰器。
     * Modifier 内置方法优先，其次是同名普通 PHP 函数；不查找 tpl_modifier_*。
     * @param mixed $value 被修饰的数据。
     * @param array<array{0:string,1:array}> $filters 名称及已求值的参数列表。
     * @return self 转换结果、末尾 escape 的原值及末尾 raw 标记。
     * @throws \InvalidArgumentException 修饰器不存在。
     */
    public static function apply($value, array $filters): self
    {
        $escape = null; $beforeEscape = null; $raw = false;
        foreach ($filters as [$name, $arguments]) {
            $normalized = strtolower($name);
            $method = [Modifier::class, $name];
            $previousValue = $value; $previousEscape = $escape; $previousBeforeEscape = $beforeEscape;
            $escape = null; $beforeEscape = null; $raw = $normalized === 'raw';
            if ($normalized === 'escape') {
                if ($value instanceof \Stringable) $value = (string) $value;
                $beforeEscape = $value;
                $escape = ($arguments[0] ?? 'html') === 'url' ? 'url' : 'html';
            }
            if (is_callable($method)) $value = $method($value, ...$arguments);
            elseif (function_exists($name)) $value = $name($value, ...$arguments);
            else throw new \InvalidArgumentException('Unknown template modifier: ' . $name);
            if ($normalized !== 'escape' && $value === $previousValue && $previousEscape !== null) {
                $escape = $previousEscape; $beforeEscape = $previousBeforeEscape;
            }
        }
        return new self($value, $raw, $escape, $beforeEscape);
    }
}
