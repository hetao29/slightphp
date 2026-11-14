<?php
namespace SlightPHP;

/** 当前 PHP 请求的数据快照。 */
class Request {
    /** @var array<string,mixed> 查询参数，保留 PHP 对重复键和数组的解析结果。 */
    public array $get;
    /** @var array<string,mixed> 表单参数；JSON 等正文通过 getContent() 读取。 */
    public array $post;
    /** @var array<string,string> 请求 Cookie。 */
    public array $cookie;
    /** @var array<string,array> 上传文件，保持 PHP 的 $_FILES 结构。 */
    public array $files;
    /** @var array<string,mixed> 服务器信息，键名统一为小写。 */
    public array $server;
    /** @var array<string,string> 请求头，键名统一为小写并使用连字符。 */
    public array $header = [];
    /** @var string[] 完整路由分段，等同于原 $inPath，包含补全的默认段。 */
    public array $route = [];
    /** @var string|false|null 缓存的原始正文；null 表示尚未读取。 */
    private $content = null;

    /** 从当前 PHP 超全局变量复制请求数据，不与其建立引用。 */
    public function __construct() {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookie = $_COOKIE;
        $this->files = $_FILES;
        $this->server = array_change_key_case($_SERVER, CASE_LOWER);
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $this->header[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $this->header[strtolower(str_replace('_', '-', $key))] = $value;
            }
        }
    }

    /** @return string|false HTTP 方法；CLI 未提供时为 false。 */
    public function getMethod() {
        return $this->server['request_method'] ?? false;
    }

    /** @return string|false 原始请求正文，按需读取并在本实例缓存；读取失败为 false。 */
    public function getContent() {
        if ($this->content === null) {
            $this->content = file_get_contents('php://input');
        }
        return $this->content;
    }

    /** @return string|false 原始请求正文。 */
    public function rawContent() {
        return $this->getContent();
    }
}
