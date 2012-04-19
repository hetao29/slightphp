<?php
/**
 * 这个例子演示如何使用数据库配置文件
 */
/**
 * 设置配置文件
  static void setConfigFile ( $file)
 * 获取配置文件
  static void getConfigFile ()
 * 获取配置信息
  static array getConfig (string $zone, [string $type = "main"])
  */
class index_dbconfig{
	function pageEntry($inPath){
		/**
		 * 第一步 设置配置文件
		 * 配置说明文档请参看 db.ini 里的注释
		 */
		SDb::setConfigFile(SlightPHP::$appDir."/index/db.ini.php");
		
		/**
		 * 第二步 获取配置
		 */
		//获取main下的主库
		print_r(SDb::getConfig("main","main"));
		//获取user下的读库
		print_r(SDb::getConfig("user","query"));
		//获取blog下的主库
		print_r(SDb::getConfig("blog","main"));
		//获取test的主库，默认为主库
		//得到的配置可以直接用于init()方法
		$db_config = SDb::getConfig("test");
		
		//获取数据库引擎
		$db = SDb::getDbEngine("pdo_mysql");
		if(!$db){
			die("DbEngine not exits");
		}
		//初始化数据库配置
		$db->init($db_config);
	}
}
?>
