<?php
namespace SlightPHP\Tpl;

/**
 * 一个模板渲染流共享的 JS 编译计划集，正文、事件属性及 include 各有独立流状态。
 *
 * 模板编译时把可预测的计划嵌入 PHP 编译文件。运行时只查表；raw、动态 include 或
 * 未走过的分支产生新入口时，首次编译该上下文变体，随后持久复用。数据的实际文本
 * 不进入计划键；由 JsContext 用不改变词法效果的占位符描述编码后的值。
 * @internal JSON 侧缓存保存状态数据，不执行侧缓存中的 PHP。
 */
final class JsPlanCache
{
    private const VERSION = 'SlightPHP Tpl JS plans v1';
    private const LIMIT = 512;
    /** @var array<string,array<string,mixed>> 当前可用计划，包括编译文件注册的计划。 */
    private array $plans = [];
    /** @var array<string,array<string,mixed>> 本次新编译的上下文变体。 */
    private array $learned = [];
    /** @var string|null null 为模板编译阶段；非空为当前外层模板的上下文侧缓存。 */
    private ?string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file;
        if ($file !== null && is_file($file)) {
            $handle = fopen($file, 'rb');
            if ($handle !== false) {
                try {
                    if (flock($handle, LOCK_SH)) $this->plans = self::read(stream_get_contents($handle));
                } finally { fclose($handle); }
            }
        }
    }

    /** 由可信的 PHP 编译文件注册；同键优先采用编译文件里的计划。 */
    public function register(array $plans): void { $this->plans = $plans + $this->plans; }

    /** @return array<string,array<string,mixed>> 模板编译结果需要嵌入的计划。 */
    public function export(): array { return $this->plans; }

    /**
     * 编译命中的键由入口状态和静态增量源码决定，编码方式不会跨不同入口误用。
     * @param array<string,mixed> $checkpoint 以第 0 字节为起点的词法状态。
     * @param string $source 尚需分析的尾部及静态增量，不是整段实际用户数据。
     * @return array<string,mixed> 已编译的编码位置和退出状态，或预期的拒绝原因。
     */
    public function get(array $checkpoint, string $source): array
    {
        $key = hash('sha256', self::VERSION . ':' . Compiler::JS_VERSION . serialize($checkpoint) . "\0" . $source);
        if (!isset($this->plans[$key])) {
            $plan = Compiler::compileJavascriptPlan($source, $checkpoint);
            $this->plans[$key] = $plan;
            if ($this->file !== null) $this->learned[$key] = $plan;
        }
        return $this->plans[$key];
    }

    /**
     * 正常渲染结束后保存新上下文，锁内合并避免并发请求互相覆盖；已命中时不写文件。
     * 侧缓存有界，旧变体可淘汰后重编译；普通数据已规范化，raw 按可信源码处理。
     * @throws \RuntimeException 新变体无法保存。
     */
    public function save(): void
    {
        if ($this->file === null || $this->learned === []) return;
        $mask = umask(0077);
        try { $handle = fopen($this->file, 'c+b'); }
        finally { umask($mask); }
        if ($handle === false) throw new \RuntimeException('Cannot create JS context plan cache');
        try {
            if (!flock($handle, LOCK_EX)) throw new \RuntimeException('Cannot lock JS context plan cache');
            $plans = array_replace(self::read(stream_get_contents($handle)), $this->learned);
            $plans = array_slice($plans, -self::LIMIT, null, true);
            $json = json_encode(['version' => self::VERSION, 'compiler' => Compiler::JS_VERSION, 'plans' => $plans], JSON_THROW_ON_ERROR);
            if (!ftruncate($handle, 0) || !rewind($handle) || fwrite($handle, $json) !== strlen($json) || !fflush($handle) || !chmod($this->file, 0600)) {
                throw new \RuntimeException('Cannot write JS context plan cache');
            }
            $this->learned = [];
        } finally { fclose($handle); }
    }

    /** 侧缓存损坏/版本不符视为编译未命中，不使用不完整的状态数据。 */
    private static function read(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || ($data['version'] ?? '') !== self::VERSION || ($data['compiler'] ?? null) !== Compiler::JS_VERSION || !is_array($data['plans'] ?? null)) return [];
        $plans = [];
        foreach ($data['plans'] as $key => $plan) {
            if (!is_string($key) || strlen($key) !== 64 || !is_array($plan)) continue;
            if (is_string($plan['error'] ?? null) || (isset($plan['kind'], $plan['expect'], $plan['escaped']) && is_array($plan['checkpoint'] ?? null) && is_string($plan['tail'] ?? null) && base64_decode($plan['tail'], true) !== false)) $plans[$key] = $plan;
        }
        return $plans;
    }
}
