<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."sphinx/sphinxapi.php");

/**
 * Sphinx 客户端分页封装。
 * @package SlightPHP
 */
class SSphinx extends SlightPHP\SphinxClient{
	private $_page=1;

	/**
	 * 设置页码并更新 Sphinx 的偏移量和查询上限。
	 * @param int $page 页码，从 1 开始。
	 * @return void
	 */
	function setPage($page){
		$this->_page = $page;
		$this->SetLimits (($this->_page-1)*$this->_limit,$this->_limit);
	}
	/**
	 * 设置每页上限并根据当前页码更新偏移量。
	 * @param int $limit 每页最大条数。
	 * @return void
	 */
	function setLimit($limit){
		$this->_limit = $limit;
		$this->SetLimits (($this->_page-1)*$this->_limit,$this->_limit);
	}
}
