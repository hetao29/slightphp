<?php

namespace SlightPHP;
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */
/**
 * 格式化并输出 PHP 错误、异常和关闭时的最后一条错误。
 * 加载本文件时会注册异常处理器、错误处理器和关闭回调。
 * 日志中可能包含调用参数；对象替换为类名，数组中密码、密钥及令牌字段脱敏。
 * @package SlightPHP
 */
class Error{

	/**
	 * @var bool 是否将错误输出到当前响应或控制台。
	 */
	public static $CONSOLE=false;
	/**
	 * @var bool 是否写入 PHP 错误日志或指定文件。
	 */
	public static $LOG=true;
	/**
	 * @var string 日志文件路径；空字符串使用 PHP 默认错误日志。
	 */
	public static $LOGFILE="";
	/**
	 * @var string 输出格式 text、html 或 json；空字符串按运行环境选择。
	 */
	public static $LOGTYPE="";


	/**
	 * @var array<int|string,string> PHP 错误级别到名称的映射。
	 */
	static $error_type=array(
		"1"	=>"E_ERROR",
		"2"	=>"E_WARNING",
		"4"	=>"E_PARSE",
		"8"	=>"E_NOTICE",
		"16"=>"E_CORE_ERROR",
		"32"=>"E_CORE_WARNING",
		"64"=>"E_COMPILE_ERROR",
		"128"=>"E_COMPILE_WARNING",
		"256"=>"E_USER_ERROR",
		"512"=>"E_USER_WARNING",
		"1024"=>"E_USER_NOTICE",
		"2047"=>"E_ALL",
		"2048"=>"E_STRICT",
		"4096"=>"E_RECOVERABLE_ERROR",
		"8192"=>"E_DEPRECATED",
		"16384"=>"E_USER_DEPRECATED",
		"30719"=>"E_ALL",
	);

	/**
	 * 格式化并记录未捕获的异常或错误。
	 * @param \Throwable $e 未捕获的异常或错误。
	 * @return void
	 */
	public static function exception_handler($e){//PHP7 Throwable
		$ct= Error::getError($e->getTrace(),$e);
		self::log($ct);
	}

	/**
	 * 处理 PHP 错误；不在 error_reporting 掩码中的错误直接忽略。
	 * @param int $errno 错误级别。
	 * @param string $errstr 错误消息。
	 * @param string $errfile 错误发生的文件。
	 * @param int $errline 错误发生的行号。
	 * @return bool|null 忽略时为 true；记录后返回 null。
	 */
	public static function error_handler($errno, $errstr, $errfile, $errline) {
		if (!(error_reporting() & $errno)) {
			return true;
		}
		$ct= Error::getError(debug_backtrace(),null);
		self::log($ct);
	}

	/**
	 * 关闭时记录 error_get_last 返回且在 error_reporting 掩码中的最后一条错误。
	 * @return void
	 */
	public static function fatal_handler() {
		$error = error_get_last();
		if ($error !== null && (error_reporting() & $error["type"])) {
			$ct = Error::getFatal($error);
			self::log($ct);
		}
	}

	/**
	 * 格式化最后一条 PHP 错误。
	 * @param array $error error_get_last 返回的错误信息。
	 * @return string|false 格式化内容；JSON 编码失败时为 false。
	 */
	private static function getFatal($error){
		$type    = self::$error_type[ $error["type"] ]??"UNKNOWN";
		$errfile = $error["file"];
		$errline = $error["line"];
		$errstr  = $error["message"];
		$logType = self::getLogType();
		if($logType=="html"){
			$html='<table border="1" cellpadding="3" style="font-size: 75%;border: 1px solid #000000;border-collapse: collapse;"><tr style="background-color: #ccccff; font-weight: bold; color: #000000;"><th style="padding:4px">#</th><th style="padding:4px">File</th><th style="padding:4px">Line</th><th style="padding:4px">Class::Method(Args)</th><th style="padding:4px">'.self::escapeHtml($type).'</th></tr>';
			$html.='<tr style="background-color: #cccccc; color: red;font-weight:bold;"><td>0</td><td style="padding:4px">'.
				self::escapeHtml($errfile).'</td><td style="padding:4px">'.
				self::escapeHtml($errline).'</td><td style="padding:4px"><td>'.
				self::escapeHtml($errstr). '</td></tr></table><hr style="background-color: #cccccc; border: 0px; height: 1px;" />';
			return $html;
		}else{
			$text=[];
			$text[]="$type (".date("Y-m-d H:i:s").")";
			$text[]=$errfile."\t".$errline."\t".$errstr;
			if($logType=="text"){
				return implode("\r\n",$text);
			}else{
				return json_encode($text);
			}
		}
	}
	/**
	 * 根据调用栈及可选异常生成日志内容。
	 * @param array $backtrace 调用栈。
	 * @param \Throwable|null $e 异常或错误对象；null 表示普通 PHP 错误。
	 * @return string|false 格式化内容；JSON 编码失败时为 false。
	 */
	private static function getError($backtrace,$e=null){
		$arrLen=count($backtrace);
		$logType = self::getLogType();
		$index=0;
		if($logType=="html"){
			$html='<table border="1" cellpadding="3" style="font-size: 75%;border: 1px solid #000000;border-collapse: collapse;"><tr style="background-color: #ccccff; font-weight: bold; color: #000000;"><th style="padding:4px">#</th><th style="padding:4px">File</th><th style="padding:4px">Line</th><th style="padding:4px">Class::Method(Args)</th><th style="padding:4px">'.(empty($e)?"Error":"Exception").'</th></tr>';
			if($arrLen>0){
				for($i=$arrLen-1;$i>0;$i--){
					$line  = isset($backtrace[$i]['line'])?$backtrace[$i]['line']:"";
					$file  = isset($backtrace[$i]['file'])?$backtrace[$i]['file']:"";
					$class = isset($backtrace[$i]['class'])?$backtrace[$i]['class']:"";
					$type = isset($backtrace[$i]['type'])?$backtrace[$i]['type']:"";
					$func = isset($backtrace[$i]['function'])?$backtrace[$i]['function']:"";
					$html.='<tr style="background-color: #cccccc; color: #000000;"><td style="padding:4px">'.($index).'</td><td style="padding:4px">'.self::escapeHtml($file).'</td><td style="padding:4px">'.self::escapeHtml($line).'</td><td style="padding:4px">'.self::escapeHtml((empty($class)?"":$class.$type).$func).'(';
					if(!empty($backtrace[$i]['args'])){
						$html.=self::escapeHtml(self::args2str($backtrace[$i]['args']));
					}
					$html.=')<td></td></tr>';
					$index++;
				}
			}
			$i=0;
			if($e){
				$html.='<tr style="background-color: #cccccc; color: #000000;"><td style="padding:4px">'.($index).'</td><td style="padding:4px">'.self::escapeHtml($e->getFile()).'</td><td style="padding:4px">'.self::escapeHtml($e->getLine()).'</td><td></td><td style="padding:4px;font-weight:bold">'.self::escapeHtml($e->getCode()).':'.self::escapeHtml($e->getMessage()).'</td></tr>';
				$index++;
			}else{
				$errorCode = $backtrace[$i]['args'][0];
				$line  = isset($backtrace[$i]['line'])?$backtrace[$i]['line']:"";
				$html.='<tr style="background-color: #cccccc; color: #000000;"><td style="padding:4px">'.($index).'</td><td style="padding:4px">'.self::escapeHtml($backtrace[$i]['args'][2]).'</td><td style="padding:4px">'.self::escapeHtml($line).'</td><td></td><td style="padding:4px;font-weight:bold">'.self::escapeHtml(Error::$error_type[$errorCode]).':'.self::escapeHtml(!empty($backtrace[$i]['args'])?$backtrace[$i]['args'][1]:"").'</td></tr>';
				$index++;
			}
			$html.='</table><hr style="background-color: #cccccc; border: 0px; height: 1px;" />';
			return $html;
		}else{
			$text=[];
			$text[]=(empty($e)?"Error":"Exception")."(".date("Y-m-d H:i:s").")";
			if($arrLen>0){
				for($i=$arrLen-1;$i>0;$i--){
					$line  = isset($backtrace[$i]['line'])?$backtrace[$i]['line']:"";
					$file  = isset($backtrace[$i]['file'])?$backtrace[$i]['file']:"";
					$class = isset($backtrace[$i]['class'])?$backtrace[$i]['class']:"";
					$type = isset($backtrace[$i]['type'])?$backtrace[$i]['type']:"";
					$func = isset($backtrace[$i]['function'])?$backtrace[$i]['function']:"";
					$tmp=($index)."\t".$file."($line)\t".(empty($class)?"":$class.'::').$func."(";
					if(!empty($backtrace[$i]['args'])){
						$tmp.=self::args2str($backtrace[$i]['args']);
					}
					$tmp.=")";
					$text[]=$tmp;
					$index++;
				}
			}
			$i=0;
			if($e){
				$text[]=($index)."\t".$e->getFile()."(".$e->getLine().")\t".$e->getCode().":".$e->getMessage();
				$index++;
			}else{
				$errorCode = $backtrace[$i]['args'][0];
				$line  = isset($backtrace[$i]['line'])?$backtrace[$i]['line']:"";
				$text[]=($index)."\t".
					@$backtrace[$i]['args'][2]."($line)\t".
					Error::$error_type[$errorCode].':'.
					(!empty($backtrace[$i]['args'])?$backtrace[$i]['args'][1]:"");
				$index++;
			}
			if($logType=="text"){
				return implode("\r\n",$text);
			}else{
				return json_encode($text);
			}
		}
	}
	/**
	 * 递归将参数中的对象替换为类名，以便序列化。
	 * @param mixed $args 按引用修改的参数值。
	 * @return void
	 */
	private static function obj2str(&$args){
		if(is_array($args)){
			foreach($args as $key => &$v){
				if(is_string($key) && preg_match('/password|passwd|secret|token|authorization|cookie|private[_-]?key|api[_-]?key|access[_-]?key|^pwd$/i', $key)){
					$v = "[REDACTED]";
					continue;
				}
				self::obj2str($v);
			}
		}elseif(is_object($args)){
			$args=get_class($args)."::";
		}
	}
	/**
	 * 将调用参数格式化为字符串。
	 * @param array $args 调用参数。
	 * @return string 格式化结果。
	 */
	private static function args2str($args){
		self::obj2str($args);
		$tmp  = json_encode($args);
		$tmp = str_replace("\/","/",$tmp);
		return substr($tmp,1,strlen($tmp)-2);
	}
	/** @return string 当前输出格式；未设置时 CLI 用 text，其他环境用 html。 */
	private static function getLogType(){
		if(self::$LOGTYPE==""){
			if(PHP_SAPI=="cli"){
				self::$LOGTYPE="text";
			}else{
				self::$LOGTYPE="html";
			}
		}
		return self::$LOGTYPE;
	}
	/** @return string 可安全插入 HTML 文本节点的内容。 */
	private static function escapeHtml($value){
		return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
	}
	/**
	 * 根据配置输出日志内容。
	 * @param string|false $ct 已格式化的内容。
	 * @return void
	 */
	private static function log($ct){
		if(Error::$CONSOLE){
			echo $ct;
		}
		if(Error::$LOG){
			if(!empty(Error::$LOGFILE)){
				error_log($ct."\n",3,Error::$LOGFILE);
			}else error_log($ct);
		}
	}
}
set_exception_handler(array(Error::class, 'exception_handler'));
set_error_handler(array(Error::class, 'error_handler'), E_ALL);
register_shutdown_function(array(Error::class, 'fatal_handler'));
