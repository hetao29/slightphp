<?php

namespace SlightPHP;
/**
 * SlightPHP 验证码插件。
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

/** 基于 Composer 安装的 Gregwar Captcha 生成图片，并使用会话保存验证码。 */
class Captcha extends CaptchaBuilder
{
	/** @var string 验证码会话键前缀。 */
	public static $session_prefix = 'SCaptcha_';
	/** @var int 验证码有效期，单位秒。 */
	public static $ttl = 300;

	/** @var string 当前实例写入会话时使用的键前缀。 */
	public $session_var;
	/** @var int 验证码最短长度。 */
	public $minWordLength = 4;
	/** @var int 验证码最长长度。 */
	public $maxWordLength = 5;
	/** @var int 图片宽度（像素）。 */
	public $width = 140;
	/** @var int 图片高度（像素）。 */
	public $height = 50;
	/** @var string 图片格式：png、jpeg 或 gif。 */
	public $imageFormat = 'png';

	/** 初始化验证码生成器及会话键前缀。 */
	public function __construct()
	{
		parent::__construct();
		$this->session_var = self::$session_prefix;
	}

	/**
	 * 生成并输出验证码图片，替换当前会话中同一前缀的旧验证码。
	 * @return string 生成的验证码文本。
	 * @throws \RuntimeException 会话未处于活动状态。
	 * @throws \InvalidArgumentException 有效期不是正整数。
	 */
	public function CreateImage(): string
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			throw new \RuntimeException('An active session is required to create a captcha');
		}
		if (!is_int(self::$ttl) || self::$ttl < 1) {
			throw new \InvalidArgumentException('Captcha ttl must be a positive integer');
		}
		$length = random_int((int) $this->minWordLength, (int) $this->maxWordLength);
		$phrase = (new PhraseBuilder($length, 'abcdefghijlmnopqrstvwyz'))->build();
		$this->setPhrase($phrase);
		$this->setImageType(in_array($this->imageFormat, ['png', 'jpeg', 'gif'], true) ? $this->imageFormat : 'jpeg');
		$this->build((int) $this->width, (int) $this->height);
		$_SESSION[$this->session_var] = ['code' => $phrase, 'expires_at' => time() + self::$ttl];
		header('Content-Type: image/' . $this->getImageType());
		$this->output();
		return $phrase;
	}

	/**
	 * 校验当前未过期的验证码，成功后移除；错误输入保留当前验证码。
	 * @param string $captcha_code 用户输入的验证码。
	 * @return bool 是否匹配。
	 */
	public static function check($captcha_code)
	{
		if (session_status() !== PHP_SESSION_ACTIVE || !is_string($captcha_code) || $captcha_code === '') {
			return false;
		}
		$key = self::$session_prefix;
		$expected = $_SESSION[$key] ?? null;
		if (!is_array($expected) || !is_string($expected['code'] ?? null)
			|| !is_int($expected['expires_at'] ?? null) || $expected['expires_at'] <= time()) {
			unset($_SESSION[$key]);
			return false;
		}
		if (!hash_equals($expected['code'], $captcha_code)) {
			return false;
		}
		unset($_SESSION[$key]);
		return true;
	}

	/**
	 * 从会话中移除验证码。
	 * @param string $captcha_code 要移除的验证码。
	 * @return void
	 */
	public static function del($captcha_code)
	{
		$expected = $_SESSION[self::$session_prefix] ?? null;
		if (is_string($captcha_code) && is_array($expected) && is_string($expected['code'] ?? null)
			&& hash_equals($expected['code'], $captcha_code)) {
			unset($_SESSION[self::$session_prefix]);
		}
	}
}

