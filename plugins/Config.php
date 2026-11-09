<?php

namespace SlightPHP;

/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */
/**
 * 解析 JSON 或 JSONC 配置，并可在进程内缓存解析结果。
 * @package SlightPHP
 */
class Config{
	/**
	 * @var bool 是否启用进程内缓存；不读写文件缓存。
	 * 缓存命中时不会检查配置文件修改时间。
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
	 * 按扩展名（不区分大小写）选择格式：.json 使用严格 JSON，
	 * .jsonc 额外支持 // 行注释及斜杠星号块注释；不再支持 .conf 等其他格式。
	 * JSON 对象解码为 stdClass，数组和标量保留原类型；不支持尾随逗号。
	 * 启用缓存时按规范路径及格式复用进程内结果，不读写旧的序列化文件缓存。
	 * @param string $configFile 配置文件路径。
	 * @return mixed JSON 解码结果。
	 * @throws \InvalidArgumentException 文件扩展名不是 .json 或 .jsonc。
	 * @throws \RuntimeException 配置文件不存在或无法读取。
	 * @throws \JsonException JSON 解码失败（包括格式、编码或深度错误）或块注释未结束。
	 */
	public static function parse($configFile){
		$extension = strtolower(pathinfo($configFile, PATHINFO_EXTENSION));
		if(!in_array($extension, ['json', 'jsonc'], true)){
			throw new \InvalidArgumentException('Configuration files must use .json or .jsonc: '.$configFile);
		}
		$realPath = realpath($configFile);
		if($realPath === false || !is_file($realPath)){
			throw new \RuntimeException('Configuration file not found: '.$configFile);
		}
		$cacheKey = $extension.':'.$realPath;
		if(self::$CACHE && array_key_exists($cacheKey, self::$_result)){
			return self::$_result[$cacheKey];
		}
		$content = @file_get_contents($realPath, false);
		if($content === false){
			throw new \RuntimeException('Cannot read configuration file: '.$configFile);
		}
		if($extension === 'jsonc'){
			$content = self::_stripJsonComments($content);
		}
		$result = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
		if(self::$CACHE){
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

	private static $_result=array();
}
