<?php
namespace SlightPHP;

/** 通过当前 PHP SAPI 输出响应；每次请求单独创建，不保存静态请求状态。 */
class Response {
    /** @var bool 是否已通过本实例输出正文。 */
    private bool $started = false;
    /** @var bool 是否已结束响应。 */
    private bool $ended = false;

    /**
     * 设置响应头，必须在正文输出前调用。
     * @param string $key 响应头名称。
     * @param string $value 响应头值，拒绝换行及 NUL。
     * @param bool $format 是否将头名称格式化为首字母大写。
     * @return bool 成功为 true；已发送、已结束或内容非法为 false。
     */
    public function header(string $key, string $value, bool $format = true): bool {
        if (!$this->canSendHeaders() || !preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D', $key)
            || strpbrk($value, "\r\n\0") !== false) {
            return false;
        }
        if ($format) {
            $key = implode('-', array_map('ucfirst', explode('-', strtolower($key))));
        }
        \header($key . ': ' . $value);
        return true;
    }

    /**
     * 设置 HTTP 状态码。
     * @param int $http_code 100 到 599 的 HTTP 状态码。
     * @param string $reason 可选原因短语，拒绝换行及 NUL。
     * @return bool 成功为 true；已发送、已结束或内容非法为 false。
     */
    public function status(int $http_code, string $reason = ''): bool {
        if (!$this->canSendHeaders() || $http_code < 100 || $http_code > 599
            || strpbrk($reason, "\r\n\0") !== false) {
            return false;
        }
        \http_response_code($http_code);
        if ($reason !== '') {
            \header('HTTP/1.1 ' . $http_code . ' ' . $reason, true, $http_code);
        }
        return true;
    }

    /**
     * 设置经过 URL 编码的 Cookie。
     * @param string $name Cookie 名称。
     * @param string $value Cookie 值；空字符串沿用 PHP 的删除 Cookie 语义。
     * @param int $expires 到期 Unix 时间戳，0 为会话 Cookie。
     * @param string $path Cookie 路径。
     * @param string $domain Cookie 域名。
     * @param bool $secure 是否仅通过 HTTPS 发送。
     * @param bool $httponly 是否禁止脚本读取。
     * @param string $samesite SameSite 属性；空字符串表示省略。
     * @return bool PHP 设置结果；响应已开始或结束时为 false。
     * @throws \ValueError PHP 拒绝非法 Cookie 名称或属性。
     */
    public function cookie(string $name, string $value = '', int $expires = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = ''): bool {
        if (!$this->canSendHeaders()) {
            return false;
        }
        return \setcookie($name, $value, compact('expires', 'path', 'domain', 'secure', 'httponly', 'samesite'));
    }

    /**
     * 设置不经过 URL 编码的 Cookie。
     * @param string $name Cookie 名称。
     * @param string $value 原始 Cookie 值；空字符串沿用 PHP 的删除语义。
     * @param int $expires 到期 Unix 时间戳，0 为会话 Cookie。
     * @param string $path Cookie 路径。
     * @param string $domain Cookie 域名。
     * @param bool $secure 是否仅通过 HTTPS 发送。
     * @param bool $httponly 是否禁止脚本读取。
     * @param string $samesite SameSite 属性；空字符串表示省略。
     * @return bool PHP 设置结果；响应已开始或结束时为 false。
     * @throws \ValueError PHP 拒绝非法 Cookie 名称、值或属性。
     */
    public function rawcookie(string $name, string $value = '', int $expires = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = ''): bool {
        if (!$this->canSendHeaders()) {
            return false;
        }
        return \setrawcookie($name, $value, compact('expires', 'path', 'domain', 'secure', 'httponly', 'samesite'));
    }

    /**
     * 输出一段正文；实际缓冲和分块由 PHP SAPI 管理。
     * @param string $content 正文，允许空字符串和字符串 "0"。
     * @return bool 已结束时为 false，否则为 true。
     */
    public function write(string $content): bool {
        if ($this->ended) {
            return false;
        }
        if ($content !== '') {
            $this->started = true;
            echo $content;
        }
        return true;
    }

    /**
     * 输出最后一段正文并标记结束，不调用 exit；控制器应 return 以停止执行。
     * @param string $content 最后一段正文，默认空字符串。
     * @return bool 重复结束为 false，否则为 true。
     */
    public function end(string $content = ''): bool {
        if (!$this->write($content)) {
            return false;
        }
        $this->ended = true;
        return true;
    }

    /**
     * 设置重定向响应并结束。
     * @param string $url Location 地址，拒绝换行及 NUL。
     * @param int $http_code 300 到 399 的重定向状态码，默认 302。
     * @return bool 成功为 true；已发送、已结束或参数非法为 false。
     */
    public function redirect(string $url, int $http_code = 302): bool {
        if ($http_code < 300 || $http_code > 399 || $url === '' || strpbrk($url, "\r\n\0") !== false
            || !$this->status($http_code) || !$this->header('Location', $url)) {
            return false;
        }
        return $this->end();
    }

    /** @return bool 本实例是否仍接受正文输出，不检测客户端连接状态。 */
    public function isWritable(): bool {
        return !$this->ended;
    }

    /** @return bool 本实例尚未输出正文且 PHP 尚未发送响应头。 */
    private function canSendHeaders(): bool {
        return !$this->started && !$this->ended && !headers_sent();
    }
}
