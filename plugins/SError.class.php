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

class SError{

	/**
	 * 
	 */
	static $CONSOLE=true;
	/**
	 * 
	 */
	static $LOG=false;
	/**
	 * 
	 */
	static $LOGFILE="";


	/**
	 * 
	 */
	static $error_type=array(
		"1"=>"E_ERROR",
		"2"=>"E_WARNING",
		"4"=>"E_PARSE",
		"8"=>"E_NOTICE",
		"16"=>"E_CORE_ERROR",
		"32"=>"E_CORE_WARNING",
		"64"=>"E_COMPILE_ERROR",
		"128"=>"E_COMPILE_WARNING",
		"256"=>"E_USER_ERROR",
		"512"=>"E_USER_WARNING",
		"1024"=>"E_USER_NOTICE",
		"2047"=>"E_ALL",
		"2048"=>"E_STRICT",
		);

	static function exception_handler(Exception $e){
		if(SError::$CONSOLE)		echo SError::getErrorHtml($e->getTrace(),$e);
		if(SError::$LOG){
			$log = SError::getErrorText($e->getTrace(),$e);
			if(!empty(SError::$LOGFILE)){
				error_log($log,3,SError::$LOGFILE);
			}else error_log($log);
		}
	}
	static function error_handler($errno, $errstr, $errfile, $errline) {
		if(SError::$CONSOLE)		echo SError::getErrorHtml(debug_backtrace());
		if(SError::$LOG){
			$log = SError::getErrorText(debug_backtrace());
			
			if(!empty(SError::$LOGFILE)){
				error_log($log,3,SError::$LOGFILE);
			}else error_log($log);
		}
	}
	/**
 	 * @return string
	 */
	function getErrorText($backtrace,$e=null){
		$arrLen=count($backtrace);
		$text="\r\n".(empty($e)?"Error":"Exception")."(".date("Y-m-d H:i:s").")\r\n";
		$index=0;
		if($arrLen>0){
			for($i=$arrLen-1;$i>0;$i--){
				$text.=($index++)."\t".$backtrace[$i]['file']."(".$backtrace[$i]['line'].")\t".(!empty($backtrace[$i]['class'])?$backtrace[$i]['class']:"").'::'.(!empty($backtrace[$i]['function'])?$backtrace[$i]['function']:"")."()\r\n";
			}
		}
		$i=0;
		if(!empty($backtrace[$i]['args']) &&!empty($backtrace[$i]['args'][0]) &&!empty($backtrace[$i]['args'][1])){
			//error
			$errorCode = $backtrace[$i]['args'][0];
			$text.=($index++)."\t".$backtrace[$i]['args'][2]."(".$backtrace[$i]['line'].")\t".SError::$error_type[$errorCode].':'.(!empty($backtrace[$i]['args'])?$backtrace[$i]['args'][1]:"")."\r\n";
		}elseif($e){
			$text.=($index++)."\t".$e->getFile()."(".$e->getLine().")\t".$e->getCode().":".$e->getMessage()."\t\r\n";
		}
		return $text;
	}
	/**
 	 * @return string
	 */
	function getErrorHtml($backtrace,$e=null){
		$arrLen=count($backtrace);
		$html="\r\n".'<table border="1" cellpadding="3" style="font-size: 75%;border: 1px solid #000000;border-collapse: collapse;"><tr style="background-color: #ccccff; font-weight: bold; color: #000000;"><th >#</th><th >File</th><th >Line</th><th >Class::Method(Args)</th><th>'.(empty($e)?"Error":"Exception").'</th></tr>';
		$index=0;
		if($arrLen>0){
			for($i=$arrLen-1;$i>0;$i--){
				$html.='<tr style="background-color: #cccccc; color: #000000;"><td>'.($index++).'</td><td>'.$backtrace[$i]['file'].'</td><td>'.$backtrace[$i]['line'].'</td><td>'.(!empty($backtrace[$i]['class'])?$backtrace[$i]['class']:"").'::'.(!empty($backtrace[$i]['function'])?$backtrace[$i]['function']:"").'(';
				if(!empty($backtrace[$i]['args'])){
					$tmpK=array();	
					foreach($backtrace[$i]['args'] as $value){
						if(is_object($value)){
							$tmpK[]=get_class ($value );
						}else{
							$tmpK[]=$value;
						}
					}
					$html.=implode(",",$tmpK);
						
				}
				$html.=')<td></td></tr>';
			}
		}
		$i=0;
		if(!empty($backtrace[$i]['args']) &&!empty($backtrace[$i]['args'][0]) &&!empty($backtrace[$i]['args'][1])){
			//error
			$errorCode = $backtrace[$i]['args'][0];
			$line = empty($backtrace[$i]['line'])?0:$backtrace[$i]['line'];
			$html.='<tr style="background-color: #cccccc; color: #000000;"><td>'.($index++).'</td><td>'.$backtrace[$i]['args'][2].'</td><td>'.$line.'</td><td></td><td style="font-weight:bold">'.SError::$error_type[$errorCode].':'.(!empty($backtrace[$i]['args'])?$backtrace[$i]['args'][1]:"").'</td></tr>';
		}elseif($e){
			$html.='<tr style="background-color: #cccccc; color: #000000;"><td>'.($index++).'</td><td>'.$e->getFile().'</td><td>'.$e->getLine().'</td><td></td><td style="font-weight:bold">'.$e->getCode().':'.$e->getMessage().'</td></tr>';
		}
		$html.='</table><hr style="background-color: #cccccc; border: 0px; height: 1px;" />'."\r\n\r\n";
		return $html;
	}

}
set_exception_handler(array('SError', 'exception_handler'));
set_error_handler(array('SError', 'error_handler'), E_ALL);
?>
