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

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."db/DbData.php");
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."db/DbObject.php");
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."db/Db.php");
/**
 * @package SlightPHP
 */
class SDb extends Db{

		static $_DbConfigFile;
		static $_DbdefaultZone="default";
		static $_DbConfigCache;
		/**
		 * @deprecated
		 * @return class Db
		 */
		static function getDbEngine($engine){
				$engine = strtolower($engine);
				if(!in_array($engine,array("mysql","pdo_mysql"))){
						return false;
				}
				if($engine=="mysql" && extension_loaded("mysql")){
						require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."db/Db_Mysql.php");
						return new Db_Mysql;
				}elseif($engine=="pdo_mysql"){
						require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."db/Db_PDO.php");
						return new Db_PDO("mysql");
				}
		}
		static function setConfigFile($file){
				SDb::$_DbConfigFile = $file;
		}
		static function getConfigFile(){
				return SDb::$_DbConfigFile;
		}
		/**
		 * @param string $zone
		 * @param string $type	main|query
		 * @return array
		 */
		static function getConfig($zone,$type="main"){
				if(!SDb::$_DbConfigFile){return;}


				$cache = &SDb::$_DbConfigCache;
				if(isset($cache[$zone]) && isset($cache[$zone][$type])){
						$i =  array_rand($cache[$zone][$type]);
						return $cache[$zone][$type][$i];
				}

				$file_data = parse_ini_file(realpath(SDb::$_DbConfigFile),true);
				if(isset($file_data[$zone])){
						$db = $file_data[$zone];
				}elseif(isset($file_data[SDb::$_DbdefaultZone])){
						$db = $file_data[SDb::$_DbdefaultZone];
				}else{
						return;
				}
				foreach($db as $key=>$row){

				}

				//no query to direct to main
				$query_flag = false;
				foreach($db as $key =>$row){
						if(strpos($key,"main")!==false){
								$index = "main";
						}elseif(strpos($key,"query")!==false){
								$index = "query";
						}else{
								continue;
						}
						$row = str_replace(":","=",$row);
						$row = str_replace(",","&",$row);
						parse_str($row,$out);
						if(!empty($out)){
								if(strpos($key,"query")!==false)$query_flag = true;
								$cache[$zone][$index][]=$out;
						}

				}
				if(!$query_flag){
						$type = "main";
				}
				$i =  array_rand($cache[$zone][$type]);
				return $cache[$zone][$type][$i];
		}




		private $foreign_keys =array();
		private $table_name=""; //表名，默认和 类名 user 一样


		/**
		 * 字段属性结构
		 * $user->id=1;
		 * $user->name="XX";
		 * $user->nickname="nickname";
		 * $user->profile = new stdclass; //重要
		 * $user->profile->address="my address";
		 * $user->profile->comment="comment";
		 */
		private $_fields;
		public function __get($k){
				if(isset($this->_fields->$k)){
						return $this->_fields->$k;
				}else{
						//判断是不是属于外键
						foreach($this->foreign_keys as $k2=>$v2){
								$tmp = explode(".",$v2);
								if(!empty($tmp[0]) && !empty($tmp[1])){
										$tbl_name = trim($tmp[0]);
										if($k==$tbl_name)
												return $this->_fields->$k = new stdclass;
								}
						}
				}
				return null;
		}
		public function __set($k,$v){
				$this->_fields->$k = $v;
		}
		/**
		 * 构造方法
		 * @param string $table_name
		 * @return void
		 **/
		public function __construct($table_name="",$config=array()){
				if(!empty($table_name))$this->table_name = $table_name;
				if(!empty($config))parent::init($config);
		}
		/*
		 * 按条件获取所有信息
		 * @param array $condition 条件，参照Db::select()里的定义
		 * @param boolean $foreign_info 是否返回外键信息
		 * @return array
		 */
		public function listAll($condition,$foreign_info=false){
				$result = parent::select($this->table_name,$condition);
				if(!empty($result->items)){
						foreach($result->items as &$r){
								//获取外键信息
								if($foreign_info){
										foreach($this->foreign_keys as $k=>$v){
												$tmp = explode(".",$v);
												if(!empty($tmp[0]) && !empty($tmp[1]) && isset($r[$k])){
														$tbl_name = trim($tmp[0]);
														$condition = array(trim($tmp[1])=>$r[$k]);
														$result2 = parent::select($tbl_name,$condition);
														if(!empty($result2->items)){
																if(count($result2->items)==1){
																		$r[$tbl_name]=$result2->items[0];
																}else{
																		$r[$tbl_name]=$result2->items;
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
		 * 重设所有参数
		 **/
		public function reset(){
				$this->_fields = new stdclass;
		}
		/**
		 * 得到带外键的信息
		 **/
		public function getAll(){
				return $this->get(true);
		}
		/**
		 * 得到信息，返回数组，可以用对像获取本身
		 **/
		public function get($foreign_info=false){
				if(!empty($this->_fields)){
						$condition=array();
						foreach($this->_fields as $k=>$v){
								if(!is_object($v))$condition[$k]=$v;
						}
						if(!empty($condition)){
								$items = $this->listAll($condition,$foreign_info);
								if($items){
										$r = $items[0];
										//设置信息
										foreach($r as $k=>$v){
												if(is_array($v)){
														foreach($v as $k2=>$v2){
																$this->_fields->$k->$k2=$v2;
														}
												}else{
														$this->_fields->$k=$v;
												}
										}
										return $r;
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
		 */
		public function set(){
				$r = false;
				if(!empty($this->_fields)){
						$condition=array();
						foreach($this->_fields as $k=>$v){
								if(!is_object($v))$condition[$k]=$v;
						}
						if(count($condition)>1){
								//当只有修改2个以上字段时才更新
								$r = parent::insert($this->table_name,$condition,false,false,$condition);
						}
						//更新外键信息
						foreach($this->foreign_keys as $k=>$v){
								//判断外键有没有值，如果没有值，必须重新获取
								//TODO
								$tmp = explode(".",$v);
								if(!empty($tmp[0]) && !empty($tmp[1])){
										$tbl_name = trim($tmp[0]);
										$filed_name = trim($tmp[1]);

										//有外键设置
										if(isset($this->_fields->$tbl_name) && is_object($this->_fields->$tbl_name)){
												//判断关联主键有没有条件
												if(!isset($this->_fields->$k)){
														$this->get(false);
												}
												//如果能获取到才修改
												if(isset($this->_fields->$k)){
														$items = $this->_fields->$tbl_name;
														//把外键条件加入进去
														$items->$filed_name = $this->_fields->$k;
														$r = parent::insert($tbl_name,$items,false,false,$items);
												}
										}
								}
						}
				}
				return $r;
		}

		/**
		 * 设置外键关联
		 */
		public function setForeignKey($keys=array()){
				$this->foreign_keys = $keys;
		}
}
