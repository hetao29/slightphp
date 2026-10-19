<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."sphinx/sphinxapi.php");

/**
 * @package SlightPHP
 */
class SSphinx extends SlightPHP\SphinxClient{
	private $_page=1;

	function setPage($page){
		$this->_page = $page;
		$this->SetLimits (($this->_page-1)*$this->_limit,$this->_limit);
	}
	function setLimit($limit){
		$this->_limit = $limit;
		$this->SetLimits (($this->_page-1)*$this->_limit,$this->_limit);
	}
}
