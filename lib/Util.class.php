<?php
class util{
	/**
	 * 判断一个字符中是不是只含有字母和数字
	 *
	 * @param unknown_type $str
	 * @return boolean
	 */
	function alnum($str)
	{
		return eregi("(^[a-zA-Z0-9]+$)",$str);

	}
	
	function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	function CN($string,$sublen,$endstr="")
	{
		if($sublen>=strlen($string))
		{
			return $string;
		}
		$s="";
		for($i=0;$i<$sublen;$i++)
		{
			if(ord($string{$i})>127)
			{
				$s.=$string{$i}.$string{++$i};
				continue;
			}else{
				$s.=$string{$i};
				continue;
			}
		}
		return $s.$endstr;
	}
	function gb2312($str)
	{
		if(function_exists("mb_convert_encoding"))
		{
			return mb_convert_encoding($str, "gbk", 'utf8,ascii,gbk');
		}else return $str;

	}
	function gbk($str)
	{	
		if(function_exists("mb_convert_encoding"))
		{
			return mb_convert_encoding($str, "gbk", 'utf8,ascii,gbk');
		}else return $str;
	}
	function utf8($str)
	{
		if(function_exists("mb_convert_encoding"))
		{
			return mb_convert_encoding($str, "utf8", 'gb2312,ascii,utf8');
		}else return $str;

	}
	function genPass($length = 8)
	{
		return substr(md5(uniqid(rand(), true)), 0, $length);
	}
	function genPass2(&$pwd,$min,$max)
	{
		for($i=0;$i<rand($min,$max);$i++){
			$num=rand(48,122);
			if(($num > 97 && $num < 122)){
				$pwd.=chr($num);
			}else if(($num > 65 && $num < 90)){
				$pwd.=chr($num);
			}else if(($num >48 && $num < 57)){
				$pwd.=chr($num);
			}else if($num==95){
				$pwd.=chr($num);
			}else{
				$i--;
			}
		}
		return $pwd;
	}
	function genPass3($pass) {
		$passwordkey = 'super.long.secret.password.key.that.will.take.forever.to.brute.force.the.md5.hash.so.anyone.trying.should.just.give.up-';
		return md5(base64_encode($passwordkey.$pass));
	}
	function getUrl($url,$timeout=60,$retry=false){
		$file=app_temp_dir . "/util_".md5($url);
		if($retry || !file_exists($file) || ((time()-$timeout) > filemtime($file))){
			$ct  = file_get_contents($url);
			$fp = gzopen($file,"w");
			gzwrite($fp,$ct);
			gzclose($fp);
		}else{
			$ct ="";
			$fp = gzopen($file,"r");
			while (!gzeof($fp)) {
			   $ct .= gzgets($fp, 4096);
			}
			gzclose($fp);
		}
		if(empty($ct) and $retry){
			//可能死循环，不管了
			return util::getUrl($url,$timeout,$retry);
		}
		return $ct;	
	}
}
?>
