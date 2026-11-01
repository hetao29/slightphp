<?php

/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * @package SlightPHP
 * @subpackage SDb
 */

namespace SlightPHP;

require_once(SLIGHTPHP_PLUGINS_DIR . "/db/DbPDO.php");
/** 通过 PDO 构造并执行查询，提供分页、参数绑定和事务操作。 */
class Db
{
	/**
	 * @var DbPDO 数据库适配器，初始化后可用。
	 */
	private $engine;
	private $params;
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
	/**
	 * 创建数据库封装并选择引擎，不会建立连接。
	 * 当前默认值 mysql 不在允许列表中，直接构造时须显式传入受支持的名称。
	 * @param string $engine_name PDO 引擎名，如 pdo_mysql；不受支持的名称会终止执行。
	 */
	function __construct($engine_name = "mysql")
	{
		$this->setEngine($engine_name);
	}
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
	/** @return bool 使用已保存参数重新连接是否成功。 */
	private function reInit()
	{
		if (empty($this->params)) {
			return false;
		}
		return $this->init($this->params);
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

		$this->params = $params;
		$this->engine = new \SlightPHP\DbPDO($this->params);
		$this->engine->init($this->params);
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
	 * @return DbData|false 查询结果；查询或计数失败时为 false。
	 */
	public function select($table, $condition = "", $item = "", $groupby = "", $orderby = "", $leftjoin = "", $hits = "")
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
	 * @return array|false 第一行，或无结果、查询失败时为 false。
	 */
	public function selectOne($table, $condition = "", $item = "", $groupby = "", $orderby = "", $leftjoin = "")
	{
		$this->setLimit(1);
		$this->setCount(false);
		$data = $this->select($table, $condition, $item, $groupby, $orderby, $leftjoin);
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
	 * 执行预处理 SQL；读取操作遇到连接错误时最多重试一次。
	 * @param string $sql SQL 语句。
	 * @param array $params PDO 占位符参数。
	 * @param int $retry 已重试次数。
	 * @return array|int|string|false 结果行、受影响行数、插入 ID 或失败时的 false。
	 */
	public function query($sql, $params = [], $retry = 0)
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
		$maxRetry = ($sql_mode == 1) ? 1 : 0;

		RETRY:
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

		if ($retry < $maxRetry && $this->engine->connectionError) {
			trigger_error(
				"DB RETRY ERROR: CODE({$this->error['code']}) SQL({$sql})",
				E_USER_NOTICE
			);
			$this->reInit();
			$retry++;
			goto RETRY;
		}

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
