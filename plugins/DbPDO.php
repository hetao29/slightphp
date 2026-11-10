<?php

/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * @package SlightPHP
 * @subpackage Db
 */

namespace SlightPHP;

/** PDO 数据库连接及最近一次预处理语句的适配器。 */
class DbPDO
{
	/** @var \PDO|null */
	private $_pdo;
	/** @var \PDOStatement|null */
	private $_stmt;
	/** @var array|null PDO 建连异常的原始 SQLSTATE、驱动错误码及消息。 */
	private $_connectionErrorInfo;

	private $_engine;

	private $_host = "localhost";
	private $_port = "3306";
	private $_user;
	private $_password;
	private $_database;

	private $_persistent;
	private $_charset;
	/**
	 * 保存连接参数；此方法不建立连接。
	 * @param array $params 连接选项：engine 为 PDO 引擎名（如 pdo_mysql），host 和 port
	 *                      为地址，user 和 password 为凭据，database 为库名，charset 为字符集，
	 *                      persistent 控制是否使用持久连接。
	 * @return void
	 */
	public function init($params = array())
	{
		foreach ($params as $key => $value) {
			$this->{"_" . $key} = $value;
		}

		/**
		 * fix pdo bug
		 * https://bugs.php.net/bug.php?id=73475
		 */
		if ($this->_persistent && $this->_password == "") {
			$this->_password = null;
		}
	}
	/**
	 * 建立 PDO 连接。当前 DSN 和连接选项按 MySQL 方式构造，即使 engine
	 * 名称指向其他 PDO 驱动，也不代表该驱动可以使用此连接方式。
	 * @return bool 连接是否成功；PDOException 会被捕获并产生警告。
	 * @throws \Error 依赖的 PDO MySQL 常量不存在时。
	 */
	public function connect()
	{
		$this->_pdo = null;
		$this->_stmt = null;
		$this->_connectionErrorInfo = null;
		$tmp = explode("_", $this->_engine);
		$driver = $tmp[1];
		try {
			$options = array(
				\PDO::ATTR_PERSISTENT => $this->_persistent,
				\PDO::ATTR_STRINGIFY_FETCHES => false,
				\PDO::ATTR_EMULATE_PREPARES => false,
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING,
			);
			if (class_exists('Pdo\Mysql')) { // PHP >=8.4
				$options[\Pdo\Mysql::ATTR_USE_BUFFERED_QUERY] = true;
				if (!empty($this->_charset)) {
					$options[\Pdo\Mysql::ATTR_INIT_COMMAND] = "SET CHARACTER SET {$this->_charset}, NAMES {$this->_charset}";
				}
			} else { //PHP <8.4
				$options[\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
				if (!empty($this->_charset)) {
					$options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET CHARACTER SET {$this->_charset}, NAMES {$this->_charset}";
				}
			}
			$this->_pdo = new \PDO(
				$driver . ":dbname=" . $this->_database . ";host=" . $this->_host . ";port=" . $this->_port,
				$this->_user,
				$this->_password,
				$options,
			);
		} catch (\PDOException  $e) {
			$this->_connectionErrorInfo = $e->errorInfo ?: [(string)$e->getCode(), null, $e->getMessage()];
			trigger_error("CONNECT DATABASE ERROR ( " . $e->getMessage() . " ) ", E_USER_WARNING);
			return false;
		}
		return true;
	}
	/**
	 * 直接执行 SQL。
	 * @param string $sql SQL 语句。
	 * @return int|false 受影响的行数，未连接或执行失败时为 false。
	 */
	public function exec($sql)
	{
		if (!$this->_pdo) return false;
		return $this->_pdo->exec($sql);
	}
	/**
	 * 预处理并执行 SQL，供后续结果和错误方法使用。
	 * @param string $sql SQL 语句。
	 * @param array $params PDO 占位符参数。
	 * @return bool 是否执行成功。
	 */
	public function query($sql, $params = [])
	{
		if (!$this->_pdo) return false;
		$this->_stmt = $this->_pdo->prepare($sql);
		if ($this->_stmt) {
			return $this->_stmt->execute($params);
		}
		return false;
	}
	/** @return array|false 最近一次语句的关联数组结果，或没有语句时为 false。 */
	public function getAll()
	{
		if (!$this->_stmt) return false;
		return $this->_stmt->fetchAll(\PDO::FETCH_ASSOC);
	}
	/** @return int|false 最近一次语句的结果列数，或没有语句时为 false。 */
	public function columnCount()
	{
		if (!$this->_stmt) return false;
		return $this->_stmt->columnCount();
	}
	/** @return int|false 最近一次语句的行数，或没有语句时为 false。 */
	public function count()
	{
		if (!$this->_stmt) return false;
		return $this->_stmt->rowCount();
	}
	/** @return bool 是否成功开始事务。 */
	public function begin()
	{
		if (!$this->_pdo) return false;
		return $this->_pdo->beginTransaction();
	}
	/** @return bool 是否成功提交事务。 */
	public function commit()
	{
		if (!$this->_pdo) return false;
		return $this->_pdo->commit();
	}
	/** @return bool 是否成功回滚事务。 */
	public function rollback()
	{
		if (!$this->_pdo) return false;
		return $this->_pdo->rollBack();
	}
	/**
	 * 使用 PDO quote 转义字符串，去掉外层引号。
	 * @param string $str 待转义的值。
	 * @return string|false 转义后的值，未连接时为 false。
	 */
	public function escape($str)
	{
		if (!$this->_pdo) return false;
		$str = $this->_pdo->quote($str);
		return substr($str, 1, strlen($str) - 2);
	}
	/** @return string|false 最后插入的 ID，或失败时为 false。 */
	public function lastId()
	{
		if (!$this->_pdo) return false;
		return $this->_pdo->lastInsertId();
	}
	/** @return array|false 最近语句或连接的 PDO errorInfo，未连接且无建连错误时为 false。 */
	public function error()
	{
		if (!$this->_pdo) return $this->_connectionErrorInfo ?? false;
		if ($this->_stmt) {
			return $this->_stmt->errorInfo();
		}
		return $this->_pdo->errorInfo();
	}
	/** @return string|null|false 最近语句或连接的 SQLSTATE，未连接且无建连错误时为 false。 */
	public function errno()
	{
		$info = $this->error();
		return $info[0] ?? false;
	}
}
