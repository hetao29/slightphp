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

/** 分页查询结果及其统计信息。 */
class DbData
{
	/**
	 * @var int 当前页码，从 1 开始。
	 */
	var $page = 1;
	/**
	 * @var int 本次返回的行数。
	 */
	var $pageSize = 0;
	/**
	 * @var int 查询限制条数。
	 */
	var $limit = 0;
	/**
	 * @var int 总页数；未启用总数统计时保持 0。
	 */
	var $totalPage = 0;
	/**
	 * @var int 总条数；未启用总数统计时保持 0。
	 */
	var $totalSize = 0;
	/**
	 * @var int|float 查询结束时间取整后减去开始时间的结果（秒），可能为负数。
	 */
	var $totalSecond = 0;
	/**
	 * @var array|null 查询结果行；查询前可能尚未设置。
	 */
	var $items; //array(array(),array());

}
