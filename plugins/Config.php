<?php

namespace SlightPHP;

use stdClass;
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */
/**
 * 按文件扩展名解析 JSON（含注释）或类似 nginx 的配置，并可缓存解析结果。
 * @package SlightPHP
 */
class Config{
	/**
	 * @var bool 是否启用进程内和临时文件缓存。
	 * 进程内命中时不会检查配置文件修改时间；临时文件缓存根据修改时间判断是否有效。
	 * 关闭缓存不会清空已有内存结果，重新启用后仍可能命中旧结果。
	 */
	public static $CACHE=true;
	/**
	 * 读取配置或顶层区域。区域值为 null 或不存在时，回退到非 null 的 default 区域。
	 * 区域按对象属性读取；JSON 顶层数组和标量适合不指定区域时直接读取。
	 * @param string $configFile 配置文件路径。
	 * @param string|null $zone 顶层区域名；为 null、空字符串或字符串 "0" 时返回完整配置。
	 * @return mixed 完整解析结果、区域值，或无可用区域时的 null。
	 * @throws \JsonException JSON 解码失败或块注释未结束。
	 **/
	public static function getConfig($configFile,$zone=null){
		$config = self::parse($configFile);
		if($zone){
			if(isset($config->$zone)){
				return $config->$zone;
			}elseif(isset($config->default)){
				return $config->default;
			}
			return null;
		}
		return $config;
	}
	/**
	 * 按扩展名（不区分大小写）选择格式：.json 和 .jsonc 支持 JSON、
	 * // 行注释及斜杠星号块注释，其他扩展名使用原有配置格式。
	 * JSON 对象解码为 stdClass，数组和标量保留原类型；不支持尾随逗号。
	 * 原有格式将重复的键或区域收集为数组；启用缓存时可复用内存或临时文件结果。
	 * 原有格式中的数字文本和 true/false 会转换成对应的 PHP 值，包括使用引号包围的值。
	 * @param string $configFile 配置文件路径。
	 * @return mixed JSON 解码结果或配置对象；缓存文件反序列化失败时可能为 false。
	 * @throws \JsonException JSON 解码失败（包括格式、编码或深度错误）或块注释未结束。
	 */
	public static function parse($configFile){
		self::$_tmpPrefix = 0;
		self::$_tmpData=array();
		$isJson = in_array(strtolower(pathinfo($configFile, PATHINFO_EXTENSION)), ['json', 'jsonc'], true);
		$cacheKey = "SConfig_Cache_".($isJson ? 'json_v1_' : '').md5(realpath($configFile));
		if(self::$CACHE){
			if(isset(self::$_result[$cacheKey])){
				return self::$_result[$cacheKey];
			}
			$tmp_file = self::_tmpDir()."/".self::$_tmpPrefix.$cacheKey;
			if(is_file($tmp_file) && filemtime($tmp_file)>=filemtime($configFile)){
				$result = unserialize(file_get_contents($tmp_file,false));
				self::$_result[$cacheKey]=$result;
				return $result;
			}
		}
		$content = file_get_contents($configFile,false);
		if($isJson){
			$result = json_decode(self::_stripJsonComments($content), false, 512, JSON_THROW_ON_ERROR);
		}else{
			//去掉注释,#号表示注释
			$content = preg_replace("/^(\s*)#(.*)/m","",$content);
			//保存临时变量,单引号,双引号里特殊字符
			$content = preg_replace_callback("/(\S+?)[\s:]+([\'\"])(.*?)\\2;/m",array(self::class,"_tmpData"),$content);
			//获取最直接的k,v值
			$result = self::_getKV($content);
			self::_split($content,$result);
		}
		if(self::$CACHE){
			file_put_contents($tmp_file,serialize($result),LOCK_EX);
			self::$_result[$cacheKey]=$result;
		}
		return $result;
	}

	/**
	 * 将字符串以外的 JSON 行注释和块注释替换为空白，避免相邻 token 合并。
	 * @param string $content JSON 文本。
	 * @return string 移除注释后的 JSON 文本。
	 * @throws \JsonException 块注释未结束。
	 */
	private static function _stripJsonComments(string $content): string {
		$result = '';
		$length = strlen($content);
		$inString = false;
		for($i = 0; $i < $length; $i++){
			$char = $content[$i];
			if($inString){
				$result .= $char;
				if($char === '\\' && $i + 1 < $length){
					$result .= $content[++$i];
				}elseif($char === '"'){
					$inString = false;
				}
			}elseif($char === '"'){
				$inString = true;
				$result .= $char;
			}elseif($char === '/' && $i + 1 < $length && $content[$i + 1] === '/'){
				$result .= ' ';
				$i += 2;
				while($i < $length && $content[$i] !== "\n" && $content[$i] !== "\r") $i++;
				if($i < $length) $result .= $content[$i];
			}elseif($char === '/' && $i + 1 < $length && $content[$i + 1] === '*'){
				$result .= ' ';
				$end = strpos($content, '*/', $i + 2);
				if($end === false) throw new \JsonException('Unterminated JSON block comment', JSON_ERROR_SYNTAX);
				$i = $end + 1;
			}else{
				$result .= $char;
			}
		}
		return $result;
	}

	private static $_tmpData=array();
	private static $_tmpPrefix="SCONFIG_TMP_PREFIX_";
	private static $_tmpIndex=0;
	private static $_result=array();
	/**
	 * @param array $matches 正则匹配结果。
	 * @return string 占位后的键值表达式。
	 */
	private static function _tmpData(array $matches): string{
		$key = self::$_tmpPrefix.(self::$_tmpIndex++);
		self::$_tmpData[$key]=$matches[3];
		return $matches[1].":".$key.";";
	}
	/**
	 * @param string $string 配置文本。
	 * @return stdClass 当前层的键值。
	 */
	private static function _getKV(string $string): stdClass {
		$_data = new stdclass;
		$tmp_string = preg_replace("/([\w\.\-\_]+?)([\s:]*)\{(([^{}]+|(?R))+)\}/","",$string);
		preg_match_all("/([\w\.\-\_]+)[\s:]+(.+?);/",$tmp_string,$_matches2,PREG_SET_ORDER);
		if(!empty($_matches2)){
			foreach($_matches2 as $_m2){
				$key = $_m2[1];
				$value= $_m2[2];


				if(isset(self::$_tmpData[$value])){$value = self::$_tmpData[$value];}

				//类型转化
				if(is_numeric($value)){
					$value = $value+0;//自动转化 
				}elseif(is_string($value)){
					$v_t = strtolower($value);;
					if($v_t == "true" ) $value =true;
					elseif($v_t == "false" )$value=false;
				}

				if(isset($_data->$key)){
					if(is_array($_data->$key)){
						array_push($_data->$key,$value);
					}else{
						$tmp2=array($_data->$key,$value);
						$_data->$key = $tmp2;
					}
				}else{
					$_data->$key = $value;
				}
			}
		}
		return $_data;
	}
	/**
	 * 递归解析嵌套区域并写入结果对象。
	 * @param string $string 配置文本。
	 * @param stdClass $result 接收解析结果的对象，按引用传递。
	 * @return void
	 */
	private static function _split (string $string, stdClass &$result): void {
		preg_match_all("/([\w\.\-\_]*?)[:\s]*\{(([^{}]*|(?R))+)\}/xms",$string,$matches,PREG_SET_ORDER);
		if (!empty($matches)) {
			foreach($matches as $m){
				if(empty($m[1]))continue;
				$_data = self::_getKV($m[2]);
				if(!isset($result->{$m[1]})){
					if(!is_array($result)){
						$result->{$m[1]} = $_data;
					}
				}else{
					if(is_array($result->{$m[1]})){
						array_push($result->{$m[1]},$_data);
					}else{
						$result->{$m[1]} = array($result->{$m[1]},$_data);
					}
				}
				self::_split($m[2], $_data);
			}
		}
	}
	/** @return string 系统临时目录路径。 */
	private static function _tmpDir(){
		if ( !function_exists('sys_get_temp_dir')){
			/** @return string|false|null 从环境或临时文件推断的临时目录。 */
			function sys_get_temp_dir() {
				if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
				if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
				if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
				$tempfile=tempnam(uniqid(rand(),TRUE),'');
				if (is_file($tempfile)) {
					unlink($tempfile);
					return realpath(dirname($tempfile));
				}
			}
		}
		return sys_get_temp_dir();
	}
}

