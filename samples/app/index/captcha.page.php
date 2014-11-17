<?php
/**
 * 这个例子演示如何使用验证码
 * 更多文档请看docs说明
 */
class index_captcha{
	function pageEntry($inPath){
		/**
		 * 初始化类
		 */
		$cap = new SCaptcha();
		
		/**
		 * 生成图片，返回验证码
		 */
		$code = $cap->CreateImage();
		/**
		 * 检验验证码
		 */
		if(SCaptcha::check($code)){
			error_log(true);
		}else{
			error_log(false);
		}	
	}
}
?>
