<?php
require_once("../SlightPHP.php");
$slight=new SlightPHP;
$slight->setDebug(true);
$slight->setSplitFlag("-_");
$slight->setDefaultZone("zone");
$slight->setAppDir(".");
$slight->setPluginsDir("../plugins");
$slight->loadPlugin("SError");
/*
 * 是否在前端显示，默认为true
 */
SError::$CONSOLE = true;
/*
 * 是否记log，默认记在error_log记的地方
 */
SError::$LOG = false;
/*
 * 指定log记在文件里，只有用SError::$LOG=true时，这个才有意义
 */
SError::$LOGFILE="/tmp/tmp_serror.log";
/*
 * 下面是测试代码
 */





echo $DDJFK;
function test($B){
	test2($B);
}
function test2($a){
	echo "$B.$a";
	//throw new Exception("D2D");
	//throw new Exception("DD");
}

echo "D";
test("FJKE","E");
	throw new Exception("DD");
echo "D";
//test("B","c");

?>
