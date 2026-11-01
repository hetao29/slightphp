<?php

/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */
if (!defined("SLIGHTPHP_PLUGINS_DIR")) define("SLIGHTPHP_PLUGINS_DIR", dirname(__FILE__));
require_once(SLIGHTPHP_PLUGINS_DIR . "/SConfig.php");
require_once(SLIGHTPHP_PLUGINS_DIR . "/db/DbData.php");
require_once(SLIGHTPHP_PLUGINS_DIR . "/db/Db.php");
/**
 * 表记录操作封装，支持简单的外键关联。
 * @package SlightPHP
 */
class SDb extends SlightPHP\Db
{
	/**
	 * @var string $_config_file global db config file
	 */
	private static $_config_file;

	/**
	 * set global db config file
	 * @param string $file 配置文件路径。
	 * @return void
	 */
	static function setConfigFile($file)
	{
		self::$_config_file = $file;
	}
	/**
	 * 读取数据库配置；指定类型不存在时回退到 main。
	 * @param string|null $zone 配置区域名。
	 * @param string $type 配置类型，如 main 或 query。
	 * @return mixed 数据库配置值，未找到时为 null。
	 */
	static function getConfig($zone = null, $type = "main")
	{
		$config = SConfig::getConfig(self::$_config_file, $zone);
		if (isset($config->$type)) {
			return $config->$type;
		} elseif (isset($config->main)) {
			return $config->main;
		}
		return;
	}
	/**
	 * Alias of setConfig
	 * @param string $zone 配置区域名。
	 * @param string $type 配置类型，如 main 或 query。
	 * @return bool|null 连接结果；配置不可用时为 null。
	 */
	public function useConfig($zone, $type = "main")
	{
		return self::setConfig($zone, $type);
	}
	/**
	 * 按配置区域建立连接；配置为数组时随机选择一项。
	 * @param string $zone 配置区域名。
	 * @param string $type 配置类型，如 main 或 query。
	 * @return bool|null 连接结果；配置不可用时为 null。
	 */
	public function setConfig($zone, $type = "main")
	{
		$config = self::getConfig($zone, $type);
		if (is_object($config)) {
			return parent::init($config);
		} elseif (is_array($config)) {
			return parent::init($config[array_rand($config)]);
		}
	}

	private $foreign_keys = array();
	private $table_name = ""; //表名


	/**
	 * 字段属性结构
	 * $user->id=1;
	 * $user->name="XX";
	 * $user->nickname="nickname";
	 * $user->profile->address="my address";
	 * $user->profile->comment="comment";
	 */
	/**
	 * @var stdclass $_fields
	 */
	private $_fields;
	/**
	 * 获取字段；外键表名对应的属性会在首次访问时创建对象。
	 * @param string $k 字段或关联表名。
	 * @return mixed 字段值、关联对象或不存在时的 null。
	 */
	public function __get(string $k)
	{
		if (isset($this->_fields->$k)) {
			return $this->_fields->$k;
		} else {
			//判断是不是属于外键
			foreach ($this->foreign_keys as $k2 => $v2) {
				$tmp = explode(".", $v2);
				if (!empty($tmp[0]) && !empty($tmp[1])) {
					$tbl_name = trim($tmp[0]);
					if ($k == $tbl_name)
						return $this->_fields->$k = new stdclass;
				}
			}
		}
		return null;
	}
	/**
	 * 设置待保存或查询的字段。
	 * @param string $k 字段名。
	 * @param mixed $v 字段值。
	 * @return void
	 */
	public function __set(string $k, $v)
	{
		$this->_fields->$k = $v;
	}
	/**
	 * 构造方法
	 * @param string $table_name 操作的表名。
	 * @param array|object $config 非空时立即用于初始化数据库连接。
	 **/
	public function __construct($table_name = "", $config = array())
	{
		if (!empty($table_name)) $this->table_name = $table_name;
		if (!empty($config)) parent::init($config);
		$this->_fields = new stdclass;
	}
	/**
	 * 设置表名
	 * @param string $table_name 后续记录操作使用的表名。
	 * @return void
	 */
	public function setTable($table_name)
	{
		$this->table_name = $table_name;
	}
	/**
	 * 按条件获取所有信息
	 * @param string|array|object $condition 条件，参照 Db::select() 的定义。
	 * @param boolean $foreign_info 是否返回外键信息
	 * @return array|false 结果行；没有结果或查询失败时为 false。
	 */
	public function listAll($condition, $foreign_info = false)
	{
		$result = parent::select($this->table_name, $condition);
		if (!empty($result->items)) {
			foreach ($result->items as &$r) {
				//获取外键信息
				if ($foreign_info) {
					foreach ($this->foreign_keys as $k => $v) {
						$tmp = explode(".", $v);
						if (!empty($tmp[0]) && !empty($tmp[1]) && isset($r[$k])) {
							$tbl_name = trim($tmp[0]);
							$condition = array(trim($tmp[1]) => $r[$k]);
							$result2 = parent::select($tbl_name, $condition);
							if (!empty($result2->items)) {
								if (count($result2->items) == 1) {
									$r[$tbl_name] = $result2->items[0];
								} else {
									$r[$tbl_name] = $result2->items;
								}
							}
						}
					}
				}
			}
			return $result->items;
		}
		return false;
	}
	/**
	 * 清空当前记录字段；表名、连接配置和外键映射保持不变。
	 * @return void
	 **/
	public function reset()
	{
		$this->_fields = new stdclass;
	}
	/**
	 * 查询首条记录并包含外键关联信息。
	 * @return array|false 查询到的记录或失败时的 false。
	 **/
	public function getAll()
	{
		return $this->get(true);
	}
	/**
	 * 使用当前非对象字段作为条件查询首条记录，并将结果写回字段。
	 * @param bool $foreign_info 是否查询关联记录。
	 * @return array|false 查询到的记录或无条件、无结果时的 false。
	 **/
	public function get($foreign_info = false)
	{
		if (!empty($this->_fields)) {
			$condition = array();
			foreach ($this->_fields as $k => $v) {
				if (!is_object($v)) $condition[$k] = $v;
			}
			if (!empty($condition)) {
				$items = $this->listAll($condition, $foreign_info);
				if ($items) {
					$r = $items[0];
					//设置信息
					foreach ($r as $k => $v) {
						if (is_array($v)) {
							foreach ($v as $k2 => $v2) {
								$this->_fields->$k->$k2 = $v2;
							}
						} else {
							$this->_fields->$k = $v;
						}
					}
					return $r;
				}
			}
		}
		return false;
	}
	/**
	 * 删除信息,可以连带外键一起删除
	 * @param bool $foreign_info 是否同时删除关联记录。
	 * @return int|false 主表删除的受影响行数，或失败时的 false。
	 **/
	public function del($foreign_info = false)
	{
		if (!empty($this->_fields)) {
			$condition = array();
			foreach ($this->_fields as $k => $v) {
				if (!is_object($v)) $condition[$k] = $v;
			}
			if (!empty($condition)) {
				if ($this->get() !== false) {
					$result = parent::delete($this->table_name, $condition);
					if ($result !== false) {
						if ($foreign_info) {
							foreach ($this->foreign_keys as $k => $v) {
								$tmp = explode(".", $v);
								if (!empty($tmp[0]) && !empty($tmp[1]) && isset($this->_fields->$k)) {
									$tbl_name = trim($tmp[0]);
									$condition = array(trim($tmp[1]) => $this->_fields->$k);
									parent::delete($tbl_name, $condition);
								}
							}
						}
					}
					return $result;
				}
			}
		}
		return false;
	}
	/**
	 * 保存信息,支持外键属性保存
	 * 当外键属性保存时，特别注意:
	 * 你必须初始化外键的值，否则可能无效，如:
	 * $test->user_profile = new stdclass;
	 * $test->user_profile->field_name = "field_value";
	 * @return string|false 最后一次插入操作的 ID；未执行或失败时为 false。
	 */
	public function set()
	{
		$r = false;
		if (!empty($this->_fields)) {
			$condition = array();
			foreach ($this->_fields as $k => $v) {
				if (!is_object($v)) $condition[$k] = $v;
			}
			if (count($condition) > 1) {
				//当只有修改2个以上字段时才更新
				$r = parent::insert($this->table_name, $condition, false, false, $condition);
			}
			//更新外键信息
			foreach ($this->foreign_keys as $k => $v) {
				//判断外键有没有值，如果没有值，必须重新获取
				//TODO
				$tmp = explode(".", $v);
				if (!empty($tmp[0]) && !empty($tmp[1])) {
					$tbl_name = trim($tmp[0]);
					$filed_name = trim($tmp[1]);

					//有外键设置
					if (isset($this->_fields->$tbl_name) && is_object($this->_fields->$tbl_name)) {
						//判断关联主键有没有条件
						if (!isset($this->_fields->$k)) {
							$this->get(false);
						}
						//如果能获取到才修改
						if (isset($this->_fields->$k)) {
							$items = $this->_fields->$tbl_name;
							//把外键条件加入进去
							$items->$filed_name = $this->_fields->$k;
							$r = parent::insert($tbl_name, $items, false, false, $items);
						}
					}
				}
			}
		}
		return $r;
	}

	/**
	 * 设置外键关联
	 * @param array $keys 本表字段名到“关联表.关联字段”的映射。
	 * @return void
	 */
	public function setForeignKey($keys = array())
	{
		$this->foreign_keys = $keys;
	}
}
