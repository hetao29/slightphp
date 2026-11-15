<?php

namespace SlightPHP;

/** 核心调度异常；只描述失败，由应用决定日志和响应方式。 */
class Exception extends \RuntimeException
{
    public const PATH_NOT_SET = 1;
    public const PATH_TOO_DEEP = 2;
    public const RECURSIVE_DISPATCH = 3;
    public const APP_DIR_INVALID = 4;
    public const FILE_NOT_FOUND = 5;
    public const FILE_NOT_ALLOWED = 6;
    public const CLASS_NOT_FOUND = 7;
    public const METHOD_NOT_FOUND = 8;
    public const METHOD_NOT_CALLABLE = 9;
    public const METHOD_NOT_ALLOWED = 10;
    public const ALIAS_NOT_FOUND = 11;
    public const INVALID_PATH = 12;

    /** @var array<string,mixed> 失败时的路径、控制器或允许方法等诊断数据。 */
    private array $context;

    /**
     * @param string $message 诊断消息，不宜直接输出给客户端。
     * @param int $code 本类定义的原因码，不是 HTTP 状态码。
     * @param array<string,mixed> $context 诊断上下文。
     * @param \Throwable|null $previous 原始异常。
     */
    public function __construct(string $message = '', int $code = 0, array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /** @return array<string,mixed> 诊断上下文；METHOD_NOT_ALLOWED 包含 allowedMethods。 */
    public function getContext(): array
    {
        return $this->context;
    }

    /** @return int 建议的 HTTP 状态码；调用本方法不会发送响应。 */
    public function getStatusCode(): int
    {
        return match ($this->getCode()) {
            self::PATH_NOT_SET, self::PATH_TOO_DEEP, self::INVALID_PATH => 400,
            self::FILE_NOT_FOUND, self::FILE_NOT_ALLOWED, self::METHOD_NOT_FOUND, self::METHOD_NOT_CALLABLE => 404,
            self::METHOD_NOT_ALLOWED => 405,
            default => 500,
        };
    }
}
