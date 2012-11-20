<?php
error_reporting(E_ALL ^E_NOTICE);
include("../www/global.php");
chdir(dirname(__FILE__));
SLog::$LOGFILE="log/run.log";
SConfig::$CACHE=false;
$info=array();
for(;;){
	usleep(1000*500);
	$config=SConfig::parse("backend.conf");
	foreach($config as $conf){
		if(!empty($conf->app) || !empty($conf->interval)){
			if(	//没有开始，或者已经超时
				empty($info[$conf->app]['starttime']) || 
				$info[$conf->app]['starttime']+$conf->interval<=time()
			  ){
				$info[$conf->app]['starttime']=time();
				$fname = _tmpDir()."/crontab.pid.".$info[$conf->app]['pid'];
				if(!empty($info[$conf->app]['pid']) && file_exists($fname)){
					$fp = fopen($fname,"r");
					if(flock($fp,LOCK_SH|LOCK_NB)==false){
						//上次运行的进程还没有结束
						fclose($fp);
						continue;
					}
					fclose($fp);
				}
				$ret= pcntl_fork();
				$command = $conf->app;
				if(!empty($conf->params)){
					$params = $conf->params;
				}else{
					$params = new stdclass;
				};
				if ($ret== -1) {//error
				} else if ($ret) {//parent
					$info[$conf->app]['pid']=$ret;
				} else {//child
					$cpid = posix_getpid();
					$fname = _tmpDir()."/crontab.pid.".$cpid;
					$fp = fopen($fname,"w");
					if(flock($fp,LOCK_EX|LOCK_NB)){
						fwrite($fp,$cpid);
						SLog::write("Exec $command");
						include($command);
					}
					fclose($fp);
					unlink($fname);
					exit;
				}
			}
		}
		pcntl_wait($c_status,WNOHANG);
	}
}

function _tmpDir(){
	if ( !function_exists('sys_get_temp_dir')){
		function sys_get_temp_dir() {
			if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
			if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
			if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
			$tempfile=tempnam(uniqid(rand(),TRUE),'');
			if (file_exists($tempfile)) {
				unlink($tempfile);
				return realpath(dirname($tempfile));
			}
		}
	}
	return sys_get_temp_dir();
}
