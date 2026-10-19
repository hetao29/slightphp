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
class DbData{
	/**
	 * @var int
	 */
	var $page=1;
	/**
	 * @var int
	 */
	var $pageSize=0;
	/**
	 * @var int
	 */
	var $limit=0;
	/**
	 * @var int
	 */
	var $totalPage=0;
	/**
	 * @var int
	 */
	var $totalSize=0;
	/**
	 * @var int
	 */
	var $totalSecond=0;
	/**
	 * @var array
	 */
	var $items;//array(array(),array());

}
