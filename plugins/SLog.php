<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * 向 PHP 日志或指定文件写入带时间和级别的日志。
 * @package SlightPHP
 */
class SLog
{
	/** @var bool 是否同时输出到 CLI 或 HTML 注释。 */
	public static $CONSOLE = false;

	/** @var bool 保留的日志开关；当前写入方法不读取此值。 */
	public static $LOG = false;

	/** @var string 日志文件路径；为空时使用 PHP 默认错误日志。 */
	public static $LOGFILE = "";
	/**
	 * @var string 数组和对象的格式：json 或其他值（print_r 文本）。
	 */
	public static $LOGTYPE="text";

	/**
	 * @var array
	 */
	public static $logLevelMap = array(
		LOG_DEBUG	=> 'DEBUG',
		LOG_INFO	=> 'INFO',
		LOG_NOTICE	=> 'NOTICE',
		LOG_WARNING	=> 'WARNING',
		LOG_ERR		=> 'ERR',
		LOG_CRIT	=> 'CRIT',
		LOG_ALERT	=> 'ALERT',
		LOG_EMERG	=> 'EMERG',
	);

	/**
	 * 写入 DEBUG 日志。
	 * @param mixed ...$args 日志内容。
	 * @return null
	 */
	public static function debug(...$args){
		return self::writeArray(self::$logLevelMap[LOG_DEBUG], $args);
	}
	/**
	 * 写入 DEBUG 级别的跟踪日志。
	 * @param mixed ...$args 日志内容。
	 * @return null
	 */
	public static function trace(...$args){
		return self::writeArray(self::$logLevelMap[LOG_DEBUG], $args);
	}
	/**
	 * 写入 NOTICE 日志。
	 * @param mixed ...$args 日志内容。
	 * @return null
	 */
	public static function notice(...$args){
		return self::writeArray(self::$logLevelMap[LOG_NOTICE], $args);
	}
	/**
	 * 写入 WARNING 日志。
	 * @param mixed ...$args 日志内容。
	 * @return null
	 */
	public static function warning(...$args){
		return self::writeArray(self::$logLevelMap[LOG_WARNING], $args);
	}
	/**
	 * 写入 ERR 日志；不会抛出异常或终止程序。
	 * @param mixed ...$args 日志内容。
	 * @return null
	 */
	public static function error(...$args){
		return self::writeArray(self::$logLevelMap[LOG_ERR], $args);
	}
	/**
	 * 写入 CRIT 日志；不会终止程序。
	 * @param mixed ...$args 日志内容。
	 * @return null
	 */
	public static function fatal(...$args){
		return self::writeArray(self::$logLevelMap[LOG_CRIT], $args);
	}

	/**
	 * 写入不带级别前缀的日志。
	 * @param mixed ...$args 日志内容。
	 * @return null
	 */
	public static function write(...$args){
		return self::writeArray("", $args);
	}
	/**
	 * 格式化并输出日志。
	 * @param string $prefix 级别前缀。
	 * @param array $args 日志内容。
	 * @return void
	 */
	private static function writeArray($prefix, Array $args){
		$log= "[" . date("Y-m-d H:i:s") . "]";
		if($prefix){
			$log.=" ".$prefix;
		}
		foreach($args as $info){
			if (is_object($info) || is_array($info)) {
				if(self::$LOGTYPE=="json"){
					$log.= " ".json_encode($info);
				}else{
					$log.= " ".print_r($info, true);
				}
			} elseif (is_bool($info)) {
				$log.= " ".($info ? "true" : "false");
			} else {
				$log.= " ".$info;
			}
		}

		if (!empty(self::$LOGFILE)) {
			error_log($log."\n", 3, self::$LOGFILE);
		} else {
			error_log($log);
		}

		if (self::$CONSOLE){
			if(PHP_SAPI=="cli"){
				echo $log."\n";
			}else{
				echo "<!--\n",$log,"\n-->";
			}
		}
	}
}
