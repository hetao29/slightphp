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
	 * 生成并输出验证码图片，同时将验证码保存到当前会话。
	 * @return string 生成的验证码文本。
	 */
	public function CreateImage(): string
	{
		$length = random_int((int) $this->minWordLength, (int) $this->maxWordLength);
		$phrase = (new PhraseBuilder($length, 'abcdefghijlmnopqrstvwyz'))->build();
		$this->setPhrase($phrase);
		$this->setImageType(in_array($this->imageFormat, ['png', 'jpeg', 'gif'], true) ? $this->imageFormat : 'jpeg');
		$this->build((int) $this->width, (int) $this->height);
		$_SESSION[$this->session_var . $phrase] = $phrase;
		header('Content-Type: image/' . $this->getImageType());
		$this->output();
		return $phrase;
	}

	/**
	 * 检查会话中是否存在匹配的验证码。
	 * @param string $captcha_code 用户输入的验证码。
	 * @return bool 是否匹配。
	 */
	public static function check($captcha_code)
	{
		return !empty($_SESSION[self::$session_prefix . $captcha_code])
			&& $_SESSION[self::$session_prefix . $captcha_code] == $captcha_code;
	}

	/**
	 * 从会话中移除验证码。
	 * @param string $captcha_code 要移除的验证码。
	 * @return void
	 */
	public static function del($captcha_code)
	{
		unset($_SESSION[self::$session_prefix . $captcha_code]);
	}
}

