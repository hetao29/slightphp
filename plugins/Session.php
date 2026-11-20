<?php
namespace SlightPHP;

/** 按需访问原生 PHP Session；配置不启动会话，也不提供协程隔离。 */
final class Session {
    /** @var string|null 会话 Cookie 名称；null 保留 PHP 当前配置。 */
    public static ?string $sessionName = null;

    /** @var array 原生 session_set_cookie_params() options；空数组保留当前配置。 */
    public static array $cookieOptions = [];

    /** @var string|null 原生缓存 limiter，默认 nocache；空字符串关闭自动缓存头，null 保留运行时配置。 */
    public static ?string $cacheLimiter = 'nocache';

    /**
     * 按需启动会话；已激活时直接复用，不重设名称、Cookie 或缓存策略。
     * @return bool 会话已激活或启动成功为 true；原生配置或启动失败为 false。
     */
    public static function start(): bool {
        if (session_status() === PHP_SESSION_ACTIVE) return true;
        if (self::$sessionName !== null && session_name(self::$sessionName) === false) return false;
        if (self::$cookieOptions !== [] && !session_set_cookie_params(self::$cookieOptions)) return false;
        if (self::$cacheLimiter !== null && session_cache_limiter(self::$cacheLimiter) === false) return false;
        return @session_start();
    }

    /**
     * 按引用返回当前会话数据，支持引用绑定和直接修改数组元素。
     * @return array 当前原生 Session 数组，不存入静态属性。
     * @throws \RuntimeException 会话启动失败，不能继续读写未激活的会话。
     */
    public static function &get(): array {
        if (!self::start()) throw new \RuntimeException('Unable to start the session');
        return $_SESSION;
    }

    /**
     * 读取当前会话 ID，或在会话未激活时指定要打开的 ID。
     * @param string|int|false|null $id 会话 ID；空值沿读取语义，数字字符串保持原值。
     * @return string|false 读取时返回当前 ID；设置时返回原 ID；设置或启动失败返回 false。
     */
    public static function id($id = null) {
        if (empty($id)) {
            if (!self::start()) return false;
            return session_id();
        }
        $previous = session_id();
        // 切换名称会清空未激活会话的 ID，先应用名称再指定目标 ID。
        if (session_status() !== PHP_SESSION_ACTIVE && self::$sessionName !== null
            && session_name(self::$sessionName) === false) return false;
        if (session_id($id) === false || !self::start()) return false;
        return $previous;
    }

    /**
     * 按需启动会话并读取实际名称；自定义名称通过 sessionName 在启动前配置。
     * @return string|false 当前名称；启动失败返回 false。
     */
    public static function name() {
        if (!self::start()) return false;
        return session_name();
    }
}
