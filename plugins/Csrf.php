<?php

namespace SlightPHP;

/**
 * Session CSRF token API；通过 Route 的 csrf 参数启用入口校验。
 * 会话未激活时按需启动；sessionHandler 可提供应用初始化回调，null 使用 Session::start()。
 * 原生启动失败统一报告 CSRF_SESSION_NOT_STARTED，抛出的原始异常通过 getPrevious() 保留。
 */
final class Csrf
{
    /** @var string 默认表单字段名；Route 的字符串 csrf 参数可覆盖当前入口的名称。 */
    public static string $fieldName = '_csrf';

    /** @var string 当前应用保存 token 的 Session 键名，由应用入口统一配置。 */
    public static string $sessionKey = '_slightphp_csrf';

    /** @var callable|null 未启动 Session 时按需调用的应用 handler；null 使用 Session::start()。 */
    public static $sessionHandler = null;

    /**
     * 获取当前会话的 token；不存在或存储格式无效时生成 32 随机字节的十六进制字符串。
     * @return string 64 位小写十六进制 token，同一会话重复调用保持不变。
     * @throws Exception Session 启动失败，原因码 CSRF_SESSION_NOT_STARTED（500）。
     */
    public static function token(): string
    {
        self::requireSession();
        return self::storedToken() ?? self::rotate();
    }

    /**
     * 生成携带当前会话 token 的 hidden input，返回 HTML 字符串，不直接输出。
     * @param string|null $fieldName 字段名；null 使用 self::$fieldName，字符串对应 Route 的 csrf 自定义名称。
     * @return string 属性值已进行 HTML 转义的 input 标签；Tpl 中可使用末尾 |raw 输出。
     * @throws \InvalidArgumentException 自定义字段名无效。
     * @throws Exception Session 启动失败，原因码 CSRF_SESSION_NOT_STARTED（500）。
     */
    public static function tokenInput(?string $fieldName = null): string
    {
        $field = self::resolveFieldName($fieldName);
        return '<input type="hidden" name="' . htmlspecialchars($field, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '" value="' . htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }

    /**
     * 生成携带当前会话 token 的 meta 标签，返回 HTML 字符串，不直接输出。
     * @param string|null $fieldName 字段名；null 使用 self::$fieldName，字符串对应 Route 的 csrf 自定义名称。
     * @return string 属性值已进行 HTML 转义的 meta 标签；Tpl 中可使用末尾 |raw 输出。
     * @throws \InvalidArgumentException 自定义字段名无效。
     * @throws Exception Session 启动失败，原因码 CSRF_SESSION_NOT_STARTED（500）。
     */
    public static function tokenMeta(?string $fieldName = null): string
    {
        $field = self::resolveFieldName($fieldName);
        return '<meta name="' . htmlspecialchars($field, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '" content="' . htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"/>';
    }

    /**
     * 校验默认或自定义 CSRF 请求头；该头不存在时读取对应 POST 表单字段。
     * 校验所有 HTTP 方法，不从查询串或 Cookie 取值，不生成或更新 token。
     * @param Request $request 当前请求快照。
     * @param string|null $fieldName 自定义表单字段与同名请求头；null 使用 self::$fieldName / X-CSRF-Token。
     * @throws \InvalidArgumentException 自定义名称为空或包含字母、数字、下划线、横线以外的字符。
     * @throws Exception Session 启动失败（CSRF_SESSION_NOT_STARTED，500），或 token 无效（CSRF_INVALID，403）。
     */
    public static function validate(Request $request, ?string $fieldName = null): void
    {
        $resolvedFieldName = self::resolveFieldName($fieldName);
        self::requireSession();
        // 与 Request 对 PHP 服务器请求头键的规范化方式一致。
        $header = $fieldName === null ? 'x-csrf-token' : strtolower(str_replace('_', '-', $fieldName));
        $expected = self::storedToken();
        $submitted = array_key_exists($header, $request->header)
            ? $request->header[$header]
            : ($request->post[$resolvedFieldName] ?? null);
        if ($expected === null || !is_string($submitted) || strlen($submitted) !== 64
            || !hash_equals($expected, $submitted)) {
            throw new Exception('CSRF token is missing or invalid', Exception::CSRF_INVALID);
        }
    }

    /**
     * 生成并保存新的 token，使旧 token 失效；应用可在登录或身份切换后调用。
     * @return string 新的 64 位小写十六进制 token。
     * @throws Exception Session 启动失败，原因码 CSRF_SESSION_NOT_STARTED（500）。
     */
    public static function rotate(): string
    {
        self::requireSession();
        return $_SESSION[self::$sessionKey] = bin2hex(random_bytes(32));
    }

    /**
     * 清除当前会话的 token，保留其他会话数据；后续 token() 调用才重新生成。
     * @throws Exception Session 启动失败，原因码 CSRF_SESSION_NOT_STARTED（500）。
     */
    public static function clear(): void
    {
        self::requireSession();
        unset($_SESSION[self::$sessionKey]);
    }

    /**
     * @param string|null $field 本次自定义名称；null 读取应用默认字段名。
     * @return string 经过校验的表单字段名；不读取 Session。
     */
    private static function resolveFieldName(?string $field): string
    {
        $field ??= self::$fieldName;
        if (!preg_match('/\A[A-Za-z0-9_-]+\z/', $field)) {
            throw new \InvalidArgumentException('Invalid CSRF field name');
        }
        return $field;
    }

    private static function requireSession(): void
    {
        if (self::$sessionKey === '') {
            throw new \InvalidArgumentException('CSRF session key must not be empty');
        }
        if (function_exists('session_status') && session_status() !== PHP_SESSION_ACTIVE) {
            if (self::$sessionHandler === null) {
                try {
                    @Session::start();
                } catch (\Throwable $error) {
                    throw new Exception('Unable to start the application session for CSRF protection',
                        Exception::CSRF_SESSION_NOT_STARTED, previous: $error);
                }
            } else {
                if (!is_callable(self::$sessionHandler)) {
                    throw new \InvalidArgumentException('CSRF session handler must be callable');
                }
                (self::$sessionHandler)();
            }
        }
        if (!function_exists('session_status') || session_status() !== PHP_SESSION_ACTIVE) {
            throw new Exception('Unable to start the application session for CSRF protection', Exception::CSRF_SESSION_NOT_STARTED);
        }
    }

    private static function storedToken(): ?string
    {
        $token = $_SESSION[self::$sessionKey] ?? null;
        return is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token) === 1 ? $token : null;
    }
}
