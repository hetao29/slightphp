<?php
/*{{{LICENSE
+-----------------------------------------------------------------------+
| SlightPHP Framework                                                   |
+-----------------------------------------------------------------------+
| This program is free software; you can redistribute it and/or modify  |
| it under the terms of the GNU General Public License as published by  |
| the Free Software Foundation. You should have received a copy of the  |
| GNU General Public License along with this program.  If not, see      |
| http://www.gnu.org/licenses/.                                         |
| Copyright (C) 2008-2009. All Rights Reserved.                         |
+-----------------------------------------------------------------------+
| Supports: http://www.slightphp.com                                    |
+-----------------------------------------------------------------------+
}}}*/

/**
 * @package SlightPHP
 * @subpackage SRest
 */
class Rest_Http{
	static private $_servers	=	array();
	static private $_serverCt	=	0;

	
	/**
	 * @param array['host']
	 * @param array['timeout']
	 * @param array['weight']
	 */
	function init($params=array()){
		if(!empty($params['host'])){
			self::addServer(
				$params['host'],
				isset($params['path'])?$params['path']:"",
				isset($params['weight'])?$params['weight']:1,
				isset($params['timeout'])?$params['timeout']:1
			);
		}
	}
	/**
	 * @param array $servers
	 */
	static function addServers($servers){
		self::$_servers=array();
		foreach($servers as $server) self::init($server);
	}
	/**
	 * consistent hashing
	 */
	public static function addServer($host,$path="",$weight=1,$timeout=1){
		$weight = $weight*10;
		for($i=1;$i<=$weight;$i++){
			$serverid = self::hash($host.":".$path.":".$i);
			self::$_servers[]=array(
				"host"=>$host,
				"path"=>$path,
				"weight"=>$weight,
				"id"=>$serverid,
				"timeout"=>$timeout,
			);
		}
		usort(self::$_servers,"self::_sort");
		self::$_serverCt=count(self::$_servers);
	}
	private static function _sort($a,$b){
		return $a['id']>$b['id'];
	}
	public static function getServer($key){
		$key = self::hash($key);
		$left = 0;
		$right=self::$_serverCt-1;
		$index = 0;
		while($left<$right-1){
			$middle = (int)(($left+$right)/2);
			if($key <= self::$_servers[$left]['id']){
				$index = $left;
				break;
			}
			if($key>= self::$_servers[$right]['id']){
				$index = $right;
				break;
			}
			$t = self::$_servers[$middle]['id'];
			if($key==$t){
				$index = $middle;
			}
			if($key>$t){
				$left = $middle;
				$index = $right;
			}else {
				$right=$middle;
				$index = $middle;
			}
		}
		$server = self::$_servers[$index];
		$server['index'] = $index;
		return $server;
	}
	private static function hash($str){
		return crc32($str);
	}
}

