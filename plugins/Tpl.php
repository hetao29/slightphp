<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * @package SlightPHP
 * @subpackage Tpl
 */
namespace SlightPHP;
/**
 * 模板入口：变量分配、编译缓存管理及当前实例中的 PHP 执行环境。
 *
 * 处理流程为 render/display -> fetch -> Compiler::compileSource -> include。
 * 6.7 起统一使用上下文自动转义，不提供模式切换接口。
 * 编译结果包含静态块、属性和 JS 候选计划，最终编码取决于实际 Context。
 *
 * 变量表属于实例；模板目录、编译目录、分隔符及编译选项由所有实例共享。
 * 同实例的 include 沿用变量和 Context；最外层 fetch 负责创建、完成及释放输出状态。
 * 常驻服务通常按请求新建实例，本类不提供协程间的静态配置隔离。
 *
 * 模板会编译为 PHP，必须来自可信来源；上下文编码不将模板变成沙箱。
 * render/display/fetch 直接写入当前输出流，既不返回完整 HTML，也不缓存最终页面数据。
 */
class Tpl
{
    /** 编译格式标记，用于自动重建旧编译文件。 */
    private const COMPILE_HEADER = 'SlightPHP Tpl compiled-plans v10';

    /** @var string 模板标签左分隔符；空值在渲染前初始化为默认值。 */
    public static $left_delimiter = '';
    /** @var string 模板标签右分隔符；空值在渲染前初始化为默认值。 */
    public static $right_delimiter = '';
    /** @var string 模板源文件目录。 */
    public static $template_dir = '';
    /** @var string 编译文件目录。 */
    public static $compile_dir = '';
    /** @var bool 是否每次都重新编译。 */
    public static $force_compile = false;

    /** @var array 当前实例的模板变量表；多次赋值与渲染之间保留。 */
    protected $_tpl_vars = array();
    /** @var \SlightPHP\Tpl\Context|null 最外层渲染期间的状态；同实例 include 共享。 */
    private ?\SlightPHP\Tpl\Context $outputContext = null;
    /** @var int 同实例 fetch 的嵌套深度；只有深度归零时结束并清理 Context。 */
    private int $renderDepth = 0;

    /**
     * 设置强制编译
     * @param bool $force_compile 是否每次重新编译。
     * @return void
     */
    public static function setForceCompile($force_compile = false) {
        self::$force_compile = $force_compile;
    }

    /**
     * 设置共享的左右分隔符，保留历史方法名 setDelimter 的拼写。
     * 普通花括号与模板语法完全相同时，可使用 literal 或改为 {% / %} 等分隔符。
     * @param string $left 左分隔符。
     * @param string $right 右分隔符。
     * @return void
     */
    public static function setDelimter($left = '{', $right = '}') {
        self::$left_delimiter = $left;
        self::$right_delimiter = $right;
    }

    /**
     * 设置模板编译路径
     * @param string $path 目录路径；空字符串使用应用目录下的 templates_c。
     * @return void
     */
    public static function setCompileDir($path = '') {
        self::$compile_dir = $path == '' ? \SlightPHP::$appDir.DIRECTORY_SEPARATOR.'templates_c' : $path;
    }

    /**
     * 设置模板文件路径
     * @param string $path 目录路径；空字符串使用应用目录下的 templates。
     * @return void
     */
    public static function setTemplateDir($path = '') {
        self::$template_dir = $path == '' ? \SlightPHP::$appDir.DIRECTORY_SEPARATOR.'templates' : $path;
    }

    /**
     * 分配一个变量或一组变量；空名称会被忽略。
     * @param string|array $tpl_var 变量名或名称到值的映射。
     * @param mixed $value 单个变量的值。
     * @return void
     */
    public function assign($tpl_var, $value = null) {
        if (is_array($tpl_var)) {
            foreach ($tpl_var as $key => $val) {
                if ($key != '') {
                    $this->_tpl_vars[$key] = $val;
                }
            }
        } elseif ($tpl_var != '') {
            $this->_tpl_vars[$tpl_var] = $value;
        }
    }

    /**
     * 初始化缺省目录/分隔符、合并本次变量，然后通过 fetch 直接输出。
     * 未覆盖的旧变量继续保留；此方法不返回 HTML 字符串。
     * @param string $tpl 模板文件名。
     * @param array $parames 分配给模板的变量。
     * @return null
     */
    public function render($tpl, $parames = array()) {
        if (self::$template_dir == '') {
            self::setTemplateDir();
        }
        if (self::$compile_dir == '') {
            self::setCompileDir();
        }
        if (self::$left_delimiter == '' || self::$right_delimiter == '') {
            self::setDelimter();
        }
        $this->assign($parames);
        return $this->fetch("$tpl");
    }

    /**
     * 别名 render
     * @param string $tpl 模板文件名。
     * @param array $parames 分配给模板的变量。
     * @return null
     */
    public function display($tpl, $parames = array()) {
        return $this->render($tpl, $parames);
    }

    /**
     * 必要时编译模板，并在当前实例作用域直接 include 编译文件。
     *
     * 源文件及编译文件必须分别位于模板目录及编译目录内；每级子目录均检查 realpath。
     * 沿用源目录 CRC32 子目录布局及 .context.php 后缀。文件头校验编译格式、
     * 分隔符及 JS 计划版本，避免重用不兼容的编译结果；不保存变量或最终 HTML。
     *
     * 嵌套 fetch 复用实例 Context，不要求子模板结束时回到文本状态；最外层才 finish。
     * 无论正常结束或异常传播，最外层都释放 Context，模板变量表则继续保留。
     * @param string $tpl 相对于模板目录的文件名。
     * @return void
     * @throws \RuntimeException 路径越界、模板缺失或编译目录及文件无法创建。
     * @throws \Throwable 编译、表达式、子模板或输出状态异常原样传播。
     */
    public function fetch($tpl) {
        if(self::$left_delimiter === '' || self::$right_delimiter === '') self::setDelimter();
        $template_dir = realpath(self::$template_dir);
        $tpl_real = realpath(self::$template_dir.DIRECTORY_SEPARATOR.$tpl);
        if($template_dir === false || $tpl_real === false || !is_file($tpl_real) ||
            !str_starts_with($tpl_real, rtrim($template_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)){
            throw new \RuntimeException('Template missing or outside template directory');
        }
        if(!is_dir(self::$compile_dir) && !@mkdir(self::$compile_dir,0700,true) && !is_dir(self::$compile_dir)){
            throw new \RuntimeException('Cannot create template compile directory');
        }
        $compile_dir = realpath(self::$compile_dir);
        if($compile_dir === false) throw new \RuntimeException('Invalid template compile directory');
        $compile_prefix = rtrim($compile_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $relative = substr($tpl_real, strlen(rtrim($template_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR));
        $parts = explode(DIRECTORY_SEPARATOR, dechex(crc32($template_dir)).DIRECTORY_SEPARATOR.$relative);
        $filename = array_pop($parts);
        // 统一使用上下文编译文件；文件头校验选项，不以模板 mtime 作为唯一有效性条件。
        $filename .= '.context.php';
        $header = '<?php /* '.self::COMPILE_HEADER.' '.hash('sha256', serialize([self::$left_delimiter, self::$right_delimiter, \SlightPHP\Tpl\Compiler::JS_VERSION])).' */ ?>';
        $compiled_file_dir = $compile_dir;
        // 逐级创建并校验，避免经过指向编译目录外的链接创建子目录。
        foreach($parts as $part){
            $dir = $compiled_file_dir.DIRECTORY_SEPARATOR.$part;
            if(!is_dir($dir) && !@mkdir($dir,0700) && !is_dir($dir)){
                throw new \RuntimeException('Cannot create compiled template subdirectory');
            }
            $compiled_file_dir = realpath($dir);
            if($compiled_file_dir === false || ($compiled_file_dir !== $compile_dir && !str_starts_with($compiled_file_dir, $compile_prefix))){
                throw new \RuntimeException('Compiled template directory outside compile directory');
            }
        }
        $compiled_file = $compiled_file_dir.DIRECTORY_SEPARATOR.$filename;
        if(file_exists($compiled_file) || is_link($compiled_file)){
            $compiled_file = realpath($compiled_file);
            if($compiled_file === false || !is_file($compiled_file) || !str_starts_with($compiled_file, $compile_prefix)){
                throw new \RuntimeException('Compiled template outside compile directory');
            }
        }
        if(self::$force_compile || !is_file($compiled_file) || filemtime($tpl_real)>filemtime($compiled_file) ||
            file_get_contents($compiled_file, false, null, 0, strlen($header)) !== $header){
            $compiled_contents = $header.\SlightPHP\Tpl\Compiler::compileSource(file_get_contents($tpl_real), self::$left_delimiter, self::$right_delimiter);
            $previousMask = umask(0077);
            try {
                if(file_put_contents($compiled_file,$compiled_contents,LOCK_EX) === false){
                    throw new \RuntimeException('Cannot write compiled template');
                }
                if(!chmod($compiled_file,0600)){
                    throw new \RuntimeException('Cannot set compiled template permissions');
                }
            } finally {
                umask($previousMask);
            }
        }
        $compiled_file = realpath($compiled_file);
        if($compiled_file === false || !is_file($compiled_file) || !str_starts_with($compiled_file, $compile_prefix)){
            throw new \RuntimeException('Compiled template outside compile directory');
        }
        // include 可跨越词法边界，因此只有最外层负责创建/结束状态机，异常也必须清理。
        $outer = $this->renderDepth === 0;
        $javascriptPlanCache = $outer ? new \SlightPHP\Tpl\JsPlanCache($compiled_file.'.js.json') : null;
        if ($javascriptPlanCache !== null) $this->outputContext = new \SlightPHP\Tpl\Context($javascriptPlanCache);
        $completed = false;
        $this->renderDepth++;
        try {
            include($compiled_file);
            if ($outer) $this->outputContext->finish();
            $completed = true;
        } catch (\Throwable $failure) {
            // 拒绝计划也可复用；保存失败不得覆盖模板本身的原始异常。
            if ($javascriptPlanCache !== null) {
                try { $javascriptPlanCache->save(); } catch (\Throwable $cacheFailure) { }
            }
            throw $failure;
        } finally {
            $this->renderDepth--;
            if ($outer) $this->outputContext = null;
            if ($completed && $javascriptPlanCache !== null) $javascriptPlanCache->save();
        }
    }

    /**
     * 发送 Location 并立即结束 PHP 执行；默认通常是 302，最终状态受 PHP 响应环境影响。
     * @param string $url 跳转地址。
     * @return never
     */
    public function redirect($url) {
        header('Location:'.$url);
        exit;
    }

    /**
     * 把可信模板原文或可信片段交给状态机，更新后续变量所处的位置。
     * @param string $text 未经本层编码的原文；该方法不会再次识别模板分隔符。
     * @return void
     */
    private function writeContextLiteral(string $text): void {
        $this->outputContext->literal($text);
    }

    /** 执行静态 HTML 转换计划，Context 负责实际入口守卫与标签组合校验。 */
    private function writeContextLiteralPlan(string $text, array $plan): void {
        $this->outputContext->plannedLiteral($text, $plan);
    }

    /**
     * 自动模式的通用值输出入口，用于未知上下文和显式 raw。
     * @param mixed $value 已完成表达式求值的结果。
     * @param bool $raw true 跳过编码，但输出内容仍参与实际上下文跟踪。
     * @return void
     */
    private function writeContextValue($value, bool $raw = false): void {
        $this->outputContext->value($value, $raw);
    }

    /** 注册编译阶段生成的 JS 编码/状态计划，include 沿用当前渲染流。 */
    private function registerJavascriptPlans(array $plans): void {
        $this->outputContext->registerJavascriptPlans($plans);
    }

    /** 执行已编译的 JS 编码调用，实参求值后仍校验实际入口。 */
    private function writeContextJavascript($value, array $hint): void {
        $this->outputContext->javascriptPlannedValue($value, $hint);
    }

    /**
     * 执行编译期已证明 HTML 文本 -> HTML 文本的静态块计划。
     *
     * 当前入口仍须检查：同一段代码可能由分支、include 或 raw 带入属性/脚本。
     * isText 不要求 DOM 元素全部闭合；这里依赖的是词法状态及非外来元素状态。
     * @param string $text 编译器确认可在普通 HTML 文本中整块输出的字面量。
     * @return void
     */
    private function writeCompiledLiteral(string $text): void {
        if ($this->outputContext->isText()) echo $text;
        else $this->outputContext->literal($text);
    }

    /**
     * 执行 HTML 文本编码候选计划；Context 不匹配或类型不适合快路径时回退。
     * @param mixed $value 参数求值在方法调用前完成，守卫因而能看到表达式造成的状态变化。
     * @return void
     */
    private function writeContextHtml($value): void {
        $this->outputContext->htmlValue($value);
    }

    /**
     * 执行属性候选计划；实际标签前缀必须与编译器给出的前缀相同。
     * @param mixed $value 已求值的变量/表达式结果。
     * @param array{tag:string,attribute:array{string,string,string},plain?:bool,url?:bool} $hint 只含模板预测信息，无请求数据。
     * @return void
     */
    private function writeContextAttribute($value, array $hint): void {
        $this->outputContext->attributePlannedValue($value, $hint);
    }

    /**
     * 在临时输出缓冲中求值，让表达式返回数据而不是绕过上下文编码直接 echo。
     *
     * 缓冲层级或内容发生变化就拒绝本次求值；finally 只清理此调用新增且可移除的缓冲。
     * 不使用无限清理循环强行关闭不可移除缓冲；模板函数仍需自行配对管理其缓冲。
     * 表达式异常原样传播，已发生的赋值、调度等业务副作用不回滚。
     *
     * @param callable $callback 编译代码生成的闭包，可读取/修改当前模板变量表。
     * @return mixed 闭包的返回值，后续仍需按真实输出位置编码。
     * @throws \LogicException 表达式产生输出或没有恢复自己的缓冲层级。
     * @throws \Throwable 闭包执行时抛出的异常。
     */
    private function evaluateContext(callable $callback) {
        $level = ob_get_level();
        ob_start();
        try {
            $value = $callback();
            if (ob_get_level() !== $level + 1 || ob_get_contents() !== '') throw new \LogicException('Context template functions must return values without output or buffer changes');
            return $value;
        } finally {
            while (ob_get_level() > $level) if (!ob_end_clean()) break;
        }
    }

    /**
     * 为编译后的 HTML 块读取纯变量/常量键数组路径，不执行 ArrayAccess 或字符串转换。
     * 仅基础 Tpl 的原生变量表可证明没有魔术访问；子类可能重载/取消属性，须完整回退。
     * 缺失键、非原生数组节点、非标量叶子均返回 null，让原有求值路径保留警告、回调及异常。
     * 返回数组可以包含 null；只有整个结果为 null 才表示不能采用直接输出路径。
     * @param array<array{0:string,1:array<int|string>}> $paths 编译器生成的只读路径。
     * @return array|null 当前渲染的数据，不写入模板缓存。
     */
    private function contextValues(array $paths): ?array {
        if (get_class($this) !== self::class || !isset($this->_tpl_vars) || !is_array($this->_tpl_vars)) return null;
        $result = [];
        foreach ($paths as [$root, $keys]) {
            if (!array_key_exists($root, $this->_tpl_vars)) return null;
            $value = $this->_tpl_vars[$root];
            foreach ($keys as $key) {
                if (!is_array($value) || !array_key_exists($key, $value)) return null;
                $value = $value[$key];
            }
            if (!is_scalar($value) && $value !== null) return null;
            $result[] = $value;
        }
        return $result;
    }

    /**
     * 在普通 HTML 文本位置执行 part，并将收集到的可信片段送回状态机。
     *
     * 模块自行负责片段内的动态数据编码；不能把任意控制器返回值放进属性或 JS 值中。
     * 与直接 Func::part 不同，此入口需要临时缓冲，以便按顺序跟踪模块产生的 HTML。
     *
     * @param string $path 交给核心调度器的入口路径。
     * @return void
     * @throws \InvalidArgumentException 调用点不在可插入 HTML 片段的文本状态。
     * @throws \LogicException 模块未恢复自己的输出缓冲层级。
     * @throws \Throwable 模块执行或片段解析失败。
     */
    private function writeContextPart($path): void {
        if (!$this->outputContext->isText()) throw new \InvalidArgumentException('part requires an HTML text context');
        $level = ob_get_level(); ob_start();
        try {
            \SlightPHP\Tpl\Func::part($path);
            if (ob_get_level() !== $level + 1) throw new \LogicException('part must restore its own output buffers');
            $content = ob_get_contents();
        } finally {
            while (ob_get_level() > $level) if (!ob_end_clean()) break;
        }
        $this->writeContextLiteral($content);
    }
}
