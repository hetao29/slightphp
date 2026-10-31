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
class Db
{
	/**
	 * 
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
	 *
	 */
	private $count = true;

	/**
	 *
	 */
	private $limit = 0;
	/**
	 *
	 */
	private $page = 1;
	/**
	 *
	 */
	private $error = array('code' => 0, 'msg' => "");
	/**
	 *
	 */
	function __construct($engine_name = "mysql")
	{
		$this->setEngine($engine_name);
	}
	public function error()
	{
		return $this->error;
	}
	private function setEngine($engine_name)
	{
		if (in_array($engine_name, $this->allow_engines)) {
			$this->engine_name = $engine_name;
		} else {
			die("Db engine: $engine_name does not support!");
		}
	}
	private function reInit()
	{
		if (empty($this->params)) {
			return false;
		}
		return $this->init($this->params);
	}
	/**
	 * construct
	 *
	 * @param array|object $params
	 * @param string $params.host
	 * @param string $params.user
	 * @param string $params.password
	 * @param string $params.database
	 * @param string $params.charset
	 * @param string $params.engine
	 * @param bool $params.persistent 
	 * @param int $param.port=3306
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
	 * is count 
	 *
	 * @param boolean count
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
	 * page number
	 *
	 * @param int page 
	 */
	public function setPage($page)
	{
		$page = intval($page);
		$this->page = $page < 1 ? 1 : $page;
	}
	/**
	 * page size
	 *
	 * @param int limit ,0 is all
	 */
	public function setLimit($limit)
	{
		$limit = intval($limit);
		$this->limit = $limit < 0 ? 0 : $limit;
	}
	/**
	 * select data from db
	 *
	 * @param string|array|object $table 
	 * @param string|array|object $condition
	 * @param string|array|object $item 
	 * @param string|array|object $groupby 
	 * @param string|array|object $orderby
	 * @param string|array|object $leftjoin
	 * @param string $hits Optimizer Hints https://dev.mysql.com/doc/refman/9.7/en/optimizer-hints.html
	 * @return \SlightPHP\DbData | boolean
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
	 * select one from select result 
	 * @return array|boolean
	 */
	public function selectOne($table, $condition = "", $item = "", $groupby = "", $orderby = "", $leftjoin = "")
	{
		$this->setLimit(1);
		$this->setCount(false);
		$data = $this->select($table, $condition, $item, $groupby, $orderby, $leftjoin);
		return $data->items[0] ?? false;
	}

	/**
	 * update data
	 *
	 * @param string|array|object $table
	 * @param string|array|object $condition
	 * @param string|array|object $item
	 * @return int|boolean
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
	 * delete
	 *
	 * @param string|array|object $table
	 * @param string|array|object $condition
	 * @return int|boolean
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
	public function escape($str)
	{
		return $this->engine->escape($str);
	}
	/**
	 * insert
	 * 
	 * @param string|array|object $table
	 * @param string|array|object $item 
	 * @param boolean $isreplace
	 * @param boolean $isdelayed
	 * @param string|array|object $update
	 * @return int|boolean int(lastInsertId or affectedRows)
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
	 * merge array
	 */
	private function merge_params(...$arr)
	{
		$arr = array_filter($arr, function ($var) {
			return ($var && is_array($var)) ? true : false;
		});
		return array_merge(...$arr);
	}
	/**
	 * @return bool | int | array
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
	 * execute sql(multi sql) 
	 * @param string $sql
	 * @return boolean
	 */
	public function execute($sql)
	{
		return $this->engine->exec($sql);
	}
	public function begin()
	{
		return $this->engine->begin();
	}
	public function commit()
	{
		return $this->engine->commit();
	}
	public function rollback()
	{
		return $this->engine->rollback();
	}
	/**
	 * @param bool $revert, orderby,conditon is false 
	 * @param bool $alias, groupby is false 
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
