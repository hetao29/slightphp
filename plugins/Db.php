<?php

namespace SlightPHP;

use stdClass;

/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */
/**
 * 数据库查询、事务和表记录操作，支持简单的外键关联。
 * @package SlightPHP
 */
class Db
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
		$config = Config::getConfig(self::$_config_file, $zone);
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
			return self::init($config);
		} elseif (is_array($config)) {
			return self::init($config[array_rand($config)]);
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
		if (!empty($config)) self::init($config);
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
		$result = self::select($this->table_name, $condition);
		if (!empty($result->items)) {
			foreach ($result->items as &$r) {
				//获取外键信息
				if ($foreign_info) {
					foreach ($this->foreign_keys as $k => $v) {
						$tmp = explode(".", $v);
						if (!empty($tmp[0]) && !empty($tmp[1]) && isset($r[$k])) {
							$tbl_name = trim($tmp[0]);
							$condition = array(trim($tmp[1]) => $r[$k]);
							$result2 = self::select($tbl_name, $condition);
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
					$result = self::delete($this->table_name, $condition);
					if ($result !== false) {
						if ($foreign_info) {
							foreach ($this->foreign_keys as $k => $v) {
								$tmp = explode(".", $v);
								if (!empty($tmp[0]) && !empty($tmp[1]) && isset($this->_fields->$k)) {
									$tbl_name = trim($tmp[0]);
									$condition = array(trim($tmp[1]) => $this->_fields->$k);
									self::delete($tbl_name, $condition);
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
				$r = self::insert($this->table_name, $condition, false, false, $condition);
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
							$r = self::insert($tbl_name, $items, false, false, $items);
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

	/**
	 * @var DbPDO 数据库适配器，初始化后可用。
	 */
	private $engine;
	private $engine_name = "pdo_mysql";
	private $allow_engines = array(
		"pdo_mysql",
		"pdo_sqlite",
		"pdo_cubrid",
		"pdo_dblib",
		"pdo_firebird",
		"pdo_ibm",
		"pdo_informix",
		"pdo_sqlsrv",
		"pdo_oci",
		"pdo_odbc",
		"pdo_pgsql"
	);

	/**
	 * @var bool select 是否查询总数。
	 */
	private $count = true;

	/**
	 * @var int 查询的每页上限，0 表示不限。
	 */
	private $limit = 0;
	/**
	 * @var int 查询页码，从 1 开始。
	 */
	private $page = 1;
	/**
	 * @var array 最近的错误信息，包含 code 和 msg。
	 */
	private $error = array('code' => 0, 'msg' => "");
	/** @return array 最近记录的错误，包含 code 和 msg。 */
	public function error()
	{
		return $this->error;
	}
	/**
	 * @param string $engine_name 受支持的 PDO 引擎名。
	 * @return void
	 */
	private function setEngine($engine_name)
	{
		if (in_array($engine_name, $this->allow_engines)) {
			$this->engine_name = $engine_name;
		} else {
			die("Db engine: $engine_name does not support!");
		}
	}
	/**
	 * 初始化并连接数据库；缺少或不支持的 engine 会回退到当前引擎名。
	 * @param array|object $params 连接选项：engine 为 PDO 引擎名，host 和 port 为地址，
	 *                             user 和 password 为凭据，database 为库名，charset 为字符集，
	 *                             persistent 控制是否使用持久连接。
	 * @return bool 连接是否成功。
	 */
	public function init($params)
	{
		if (is_object($params)) $params = (array)$params;

		if (!isset($params['engine']) || !in_array($params['engine'], $this->allow_engines)) {
			$params['engine'] = $this->engine_name;
		}
		$this->setEngine($params['engine']);

		$this->engine = new \SlightPHP\DbPDO();
		$this->engine->init($params);
		if ($this->engine->connect() === false) {
			$this->error['code'] = $this->engine->errno();
			$this->error['msg'] = $this->engine->error();
			if (defined("DEBUG")) {
				trigger_error("{$this->engine_name} ( " . print_r($this->error, true) . ")");
			}
			return false;
		}
		return true;
	}
	/**
	 * 控制下次 select 是否计算总数。新实例默认开启；每次 select 后关闭。
	 *
	 * @param bool $count 是否计算总数。
	 * @return void
	 */
	public function setCount($count)
	{
		if ($count == true) {
			$this->count = true;
		} else {
			$this->count = false;
		}
	}
	/**
	 * 设置下次查询页码，小于 1 时按 1 处理。
	 *
	 * @param int $page 页码。
	 * @return void
	 */
	public function setPage($page)
	{
		$page = intval($page);
		$this->page = $page < 1 ? 1 : $page;
	}
	/**
	 * 设置下次查询的每页上限。
	 *
	 * @param int $limit 0 或负数表示不限。
	 * @return void
	 */
	public function setLimit($limit)
	{
		$limit = intval($limit);
		$this->limit = $limit < 0 ? 0 : $limit;
	}
	/**
	 * 查询记录；执行后页码重置为 1、上限重置为 0、总数统计关闭。
	 * 条件中的关联键值使用占位符绑定；字符串和数字键元素作为原始 SQL，
	 * 表名、字段名及其他 SQL 片段也不会自动转义，应由调用方确保可信。
	 * 启用总数统计且设置了上限时会执行额外的 COUNT 查询；分组查询只使用
	 * COUNT 结果的第一行，因此 totalSize 不是分组总数。
	 *
	 * @param string|array|object $table 表名或表与别名的映射。
	 * @param string|array|object $condition 原始条件或字段到绑定值的映射。
	 * @param string|array|object $item 查询字段；空值表示全部字段。
	 * @param string|array|object $groupby 分组表达式。
	 * @param string|array|object $orderby 排序表达式。
	 * @param string|array|object $leftjoin LEFT JOIN 表与 ON 条件。
	 * @param string $hits 原样放入 SELECT 优化器提示中的内容。
	 * @param string $for 锁模式；UPDATE/SHARE 在 LIMIT 之后追加 FOR UPDATE/FOR SHARE，其他值不追加。
	 * @return DbData|false 查询结果；查询或计数失败时为 false。
	 */
	public function select($table, $condition = "", $item = "", $groupby = "", $orderby = "", $leftjoin = "", $hits = "", $for = "")
	{
		//TABLE
		$table = $this->buildsql($table, "AS");
		//condition
		$params = [];
		$condition_str = "";
		if (!empty($condition)) {
			$condition_str = "WHERE " . $this->buildsql($condition, "=", false, true, "AND", $params);
		}
		//ITEM
		if (!empty($item)) {
			$item = $this->buildsql($item, "AS");
		} else {
			$item = "*";
		}
		//GROUPBY
		$groupby_str = "";
		if (!empty($groupby)) {
			$groupby_str = "GROUP BY " . $this->buildsql($groupby, "", true, false);
		}
		//LEFTJOIN
		$leftjoin_str = "";
		if (!empty($leftjoin)) {
			if (is_array($leftjoin) || is_object($leftjoin)) {
				foreach ($leftjoin as $k => $_v) {
					$v = $_v;
					if (is_array($_v)) {
						$v = implode(" AND ", $_v);
					}
					$leftjoin_str .= " LEFT JOIN $k ON $v ";
				}
			} else {
				$leftjoin_str = " LEFT JOIN $leftjoin";
			}
		}
		//ORDERBY
		$orderby_str = "";
		if (!empty($orderby)) {
			$orderby_str = "ORDER BY " . $this->buildsql($orderby, "", false);
		}

		$limit_sql = "";
		if ($this->limit > 0) {
			$limit     = ($this->page - 1) * $this->limit;
			$limit_sql = "LIMIT $limit,$this->limit";
		}
		$hits_str = "";
		if (!empty($hits)) {
			$hits_str = " /*+ {$hits} */ ";
		}
		$sql = "SELECT $hits_str $item FROM ($table) $leftjoin_str $condition_str $groupby_str $orderby_str $limit_sql";
		if ($for === "UPDATE" || $for === "SHARE") {
			$sql .= " FOR " . $for;
		}
		$start = microtime(true);
		$result = $this->query($sql, $params);
		if ($result !== false) {
			$data = new DbData;
			$count_failed = false;
			$data->page = (int)$this->page;
			$data->limit = (int)$this->limit;
			$data->items = $result;
			$data->pageSize = (int)count($data->items);
			//{{{
			if ($this->count == true) {
				if ($this->limit > 0) {
					$countsql = "SELECT $hits_str count(1) totalSize FROM ($table) $leftjoin_str $condition_str $groupby_str";
					$result_count = $this->query($countsql, $params);
					if ($result_count === false) {
						$count_failed = true;
					} elseif (!empty($result_count[0])) {
						$data->totalSize = (int)$result_count[0]['totalSize'];
						$data->totalPage = (int)ceil($data->totalSize / $data->limit);
					}
				} else {
					$data->totalSize = $data->pageSize;
					$data->totalPage = 1;
				}
			}
			//}}}
			$end = microtime(true);
			$data->totalSecond = (int)$end - $start;
			$result = $count_failed ? false : $data;
		}
		//{{{reset 
		$this->setPage(1);
		$this->setLimit(0);
		$this->setCount(false);
		//}}}
		return $result;
	}
	/**
	 * 查询第一条记录，使用与 select 相同的参数。
	 * @param string|array|object $table 表名或表与别名的映射。
	 * @param string|array|object $condition 查询条件。
	 * @param string|array|object $item 查询字段。
	 * @param string|array|object $groupby 分组表达式。
	 * @param string|array|object $orderby 排序表达式。
	 * @param string|array|object $leftjoin LEFT JOIN 条件。
	 * @param string $hits 原样传递给 select 的优化器提示。
	 * @param string $for 锁模式；UPDATE/SHARE 表示 FOR UPDATE/FOR SHARE，须由调用方管理事务和连接。
	 * @return array|false 第一行，或无结果、查询失败时为 false。
	 */
	public function selectOne($table, $condition = "", $item = "", $groupby = "", $orderby = "", $leftjoin = "", $hits = "", $for = "")
	{
		$this->setLimit(1);
		$this->setCount(false);
		$data = $this->select($table, $condition, $item, $groupby, $orderby, $leftjoin, $hits, $for);
		return $data->items[0] ?? false;
	}

	/**
	 * 更新匹配条件的记录；字符串 SQL 片段及数字键元素会原样拼接。
	 *
	 * @param string|array|object $table 表名或表与别名的映射。
	 * @param string|array|object $condition 原始条件或字段到绑定值的映射；空条件更新所有行。
	 * @param string|array|object $item 原始 SET 片段或字段到绑定值的映射。
	 * @param string|array|object|null $innerjoin INNER JOIN 表与 ON 条件。
	 * @return int|false 受影响的行数或失败时的 false。
	 */
	public function update($table, $condition, $item, $innerjoin = NULL)
	{
		$table = $this->buildsql($table, "AS");
		$params = [];
		$params2 = [];
		$value = $this->buildsql($item, "=", false, true, ",", $params);
		$condition_str = $this->buildsql($condition, "=", false, true, "AND", $params2);
		if ($condition_str != "") {
			$condition_str = " WHERE " . $condition_str;
		}
		//INNER JOIN
		$innerjoin_str = "";
		if (!empty($innerjoin)) {
			if (is_array($innerjoin) || is_object($innerjoin)) {
				foreach ($innerjoin as $k => $_v) {
					$v = $_v;
					if (is_array($_v)) {
						$v = implode(" AND ", $_v);
					}
					$innerjoin_str .= " INNER JOIN $k ON $v ";
				}
			} else {
				$innerjoin_str = " INNER JOIN $innerjoin";
			}
		}
		$sql = "UPDATE $table $innerjoin_str SET $value $condition_str";
		return $this->query($sql, $this->merge_params($params, $params2));
	}
	/**
	 * 删除匹配条件的记录；字符串 SQL 片段及数字键元素会原样拼接。
	 *
	 * @param string|array|object $table 表名或表与别名的映射。
	 * @param string|array|object $condition 原始条件或字段到绑定值的映射；空条件删除所有行。
	 * @return int|false 受影响的行数或失败时的 false。
	 */
	public function delete($table, $condition)
	{
		$table = $this->buildsql($table, "AS");
		$params = [];
		$condition_str = $this->buildsql($condition, "=", false, true, "AND", $params);
		if ($condition_str != "") {
			$condition_str = " WHERE " . $condition_str;
		}
		$sql = "DELETE FROM  $table $condition_str";
		return $this->query($sql, $params);
	}
	/**
	 * @param string $str 待转义的字符串。
	 * @return string|false 不含外层引号的转义值，或失败时的 false。
	 */
	public function escape($str)
	{
		return $this->engine->escape($str);
	}
	/**
	 * 插入记录，可选择 REPLACE、IGNORE 和重复键更新；字符串 SQL 片段及数字键元素会原样拼接。
	 * 
	 * @param string|array|object $table 表名或表与别名的映射。
	 * @param string|array|object $item 原始 SET 片段或字段到绑定值的映射。
	 * @param bool $isreplace 是否使用 REPLACE。
	 * @param bool $isdelayed 是否添加 DELAYED 修饰符。
	 * @param string|array|object $update 重复键时的更新表达式或字段值映射。
	 * @param bool $ignore 是否使用 INSERT IGNORE（REPLACE 时无效）。
	 * @return string|false 最后插入 ID，或失败时的 false。
	 */
	public function insert($table, $item = "", $isreplace = false, $isdelayed = false, $update = array(), $ignore = false)
	{
		$table = $this->buildsql($table, "AS");
		if ($isreplace == true) {
			$command = "REPLACE";
		} else {
			if ($ignore) {
				$command = "INSERT IGNORE";
			} else {
				$command = "INSERT";
			}
		}
		if ($isdelayed == true) {
			$command .= " DELAYED ";
		}

		$params = [];
		$params2 = [];
		$f = $this->buildsql($item, "=", false, true, ",", $params);

		$sql = "$command INTO $table SET $f ";
		$v = $this->buildsql($update, "=", false, true, ",", $params2);
		if (!empty($v)) {
			$sql .= "ON DUPLICATE KEY UPDATE $v";
		}
		return $this->query($sql, $this->merge_params($params, $params2));
	}

	/**
	 * 合并非空参数数组，保留绑定参数的顺序。
	 * @param array ...$arr 待合并的参数数组。
	 * @return array 合并后的参数。
	 */
	private function merge_params(array ...$arr): array
	{
		$arr = array_filter($arr, function ($var) {
			return ($var && is_array($var)) ? true : false;
		});
		return array_merge(...$arr);
	}
	/**
	 * 执行预处理 SQL；失败直接返回 false，不自动重连或重试。
	 * @param string $sql SQL 语句。
	 * @param array $params PDO 占位符参数。
	 * @return array|int|string|false 结果行、受影响行数、插入 ID 或失败时的 false。
	 */
	public function query($sql, $params = [])
	{
		$sql_mode = 1; //查询
		$command = strtoupper(strtok(ltrim($sql), " \n\r\t"));
		switch ($command) {
			case 'UPDATE':
			case 'DELETE':
			case 'ALTER':
			case 'DROP':
			case 'CREATE':
			case 'TRUNCATE':
			case 'RENAME':
				$sql_mode = 2; //更新
				break;
			case 'INSERT':
			case 'REPLACE':
				$sql_mode = 3; //插入
				break;
		}
		if (defined('DEBUG')) {
			trigger_error("{$this->engine_name} ( {$sql} )");
		}
		$result = $this->engine->query($sql, $params);
		if ($result !== false) {
			if ($this->engine->columnCount() > 0) {
				return $this->engine->getAll();
			}
			if ($sql_mode == 3) {
				return $this->engine->lastId();
			}
			return $this->engine->count();
		}

		// 错误信息
		$this->error = [
			'code' => $this->engine->errno(),
			'msg'  => $this->engine->error(),
		];

		trigger_error(
			"DB QUERY ERROR: CODE({$this->error['code']}) SQL({$sql})",
			E_USER_WARNING
		);
		return false;
	}
	/**
	 * 直接执行 SQL，适用于不需要参数绑定的语句。
	 * @param string $sql SQL 语句。
	 * @return int|false 受影响行数，失败时为 false。
	 */
	public function execute($sql)
	{
		return $this->engine->exec($sql);
	}
	/** @return bool 是否成功开始事务。 */
	public function begin()
	{
		return $this->engine->begin();
	}
	/** @return bool 是否成功提交事务。 */
	public function commit()
	{
		return $this->engine->commit();
	}
	/** @return bool 是否成功回滚事务。 */
	public function rollback()
	{
		return $this->engine->rollback();
	}
	/**
	 * 将字符串或键值集合转换为 SQL 片段；提供参数数组时绑定关联键的值。
	 * @param string|array|object $mixed SQL 片段或表达式集合。
	 * @param string $split 键和值之间的运算符。
	 * @param bool $revert 是否交换关联键和值。
	 * @param bool $alias 是否处理关联键。
	 * @param string $joinflag 表达式之间的连接符。
	 * @param array|null $return_params 按引用接收绑定值；null 表示直接拼接。
	 * @return string 生成的 SQL 片段。
	 */
	private function buildsql($mixed, $split = "", $revert = true, $alias = true, $joinflag = ",", &$return_params = NULL)
	{
		if (is_array($mixed) || is_object($mixed)) {
			$split = $split == "" ? " " : " {$split} ";
			$tmp = array();
			foreach ($mixed as $k => $v) {
				if ($alias && !is_int($k)) {
					if ($revert) {
						[$v, $k] = [$k, $v]; //swap
					}
					if ($return_params !== NULL) {
						if ($v === NULL && $joinflag == "AND") {
							$tmp[] = "$k IS NULL";
							continue;
						}
						if (($ct = substr_count($k, "?")) > 0) {
							$is_array = is_array($v);
							$tmp[] = $k;
							for ($i = 0; $i < $ct; $i++) {
								if ($is_array) {
									$return_params[] = $v[$i] ?? '';
								} else {
									$return_params[] = $v;
								}
							}
							continue;
						} else {
							$return_params[] = $v;
						}
						$v = "?";
					}
					$tmp[] = $k . $split . $v;
				} else {
					$tmp[] = $v;
				}
			}
			$mixed = implode(" $joinflag ", $tmp);
		}
		return $mixed;
	}
}
