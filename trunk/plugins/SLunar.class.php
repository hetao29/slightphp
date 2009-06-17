<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Hetal <hetao@hetao.name>                                    |
  |          SlightPHP <admin@slightphp.com>                             |
  |          http://www.slightphp.com                                    |
  +----------------------------------------------------------------------+
*/
/*阳历转换为农历方法*/

class SLunar {
	/**
	 * 农历年分
	 * @var int $year
	 */
	var $year;
	/**
	 * 农历月分
	 * @var int $month
	 */
	var $month;
	/**
	 * 农历日期
	 * @var int $day
	 */
	var $day;
	/**
	 * 在本年中的第几日，保留
	 * @var int $days
	 */
	//var $days;
	/**
	 * 是否是润年
	 * @var boolean $isLeap
	 */
	var $isLeap;
	/**
	 * 离1900之间有多少个年份
	 * @var int $yearCyl
	 */
	var $yearCyl;
	/**
	 * 离1900之间有多少天
	 * @var int $dayCyl
	 */
	var $dayCyl;
	/**
	 * 离1900之间有多少个月份
	 * @var int $monCyl
	 */
	var $monCyl;
	private $lunarInfo = array(
		0x04bd8,0x04ae0,0x0a570,0x054d5,0x0d260,0x0d950,0x16554,0x056a0,0x09ad0,0x055d2,
		0x04ae0,0x0a5b6,0x0a4d0,0x0d250,0x1d255,0x0b540,0x0d6a0,0x0ada2,0x095b0,0x14977,
		0x04970,0x0a4b0,0x0b4b5,0x06a50,0x06d40,0x1ab54,0x02b60,0x09570,0x052f2,0x04970,
		0x06566,0x0d4a0,0x0ea50,0x06e95,0x05ad0,0x02b60,0x186e3,0x092e0,0x1c8d7,0x0c950,
		0x0d4a0,0x1d8a6,0x0b550,0x056a0,0x1a5b4,0x025d0,0x092d0,0x0d2b2,0x0a950,0x0b557,
		0x06ca0,0x0b550,0x15355,0x04da0,0x0a5d0,0x14573,0x052d0,0x0a9a8,0x0e950,0x06aa0,
		0x0aea6,0x0ab50,0x04b60,0x0aae4,0x0a570,0x05260,0x0f263,0x0d950,0x05b57,0x056a0,
		0x096d0,0x04dd5,0x04ad0,0x0a4d0,0x0d4d4,0x0d250,0x0d558,0x0b540,0x0b5a0,0x195a6,
		0x095b0,0x049b0,0x0a974,0x0a4b0,0x0b27a,0x06a50,0x06d40,0x0af46,0x0ab60,0x09570,
		0x04af5,0x04970,0x064b0,0x074a3,0x0ea50,0x06b58,0x055c0,0x0ab60,0x096d5,0x092e0,
		0x0c960,0x0d954,0x0d4a0,0x0da50,0x07552,0x056a0,0x0abb7,0x025d0,0x092d0,0x0cab5,
		0x0a950,0x0b4a0,0x0baa4,0x0ad50,0x055d9,0x04ba0,0x0a5b0,0x15176,0x052b0,0x0a930,
		0x07954,0x06aa0,0x0ad50,0x05b52,0x04b60,0x0a6e6,0x0a4e0,0x0d260,0x0ea65,0x0d530,
		0x05aa0,0x076a3,0x096d0,0x04bd7,0x04ad0,0x0a4d0,0x1d0b6,0x0d250,0x0d520,0x0dd45,
		0x0b5a0,0x056d0,0x055b2,0x049b0,0x0a577,0x0a4b0,0x0aa50,0x1b255,0x06d20,0x0ada0,
		0x14b63);

	/**
	* 传回农历 y年的总天数
	*/
	function lYearDays($y) {
		$sum = 348;
		for($i=0x8000; $i>0x8; $i>>=1)
			$sum += ($this->lunarInfo[$y-1900] & $i)? 1: 0;
		return $sum+$this->leapDays($y);
	}
	/**
	* 传回农历 y年闰月的天数
	*/
	function leapDays($y) {
		if($this->leapMonth($y))
			return ($this->lunarInfo[$y-1900] & 0x10000)? 30: 29;
		else return 0;
	}
	/**
	* 传回农历 y年闰哪个月 1-12 , 没闰传回 0
	*/
	function leapMonth($y) {
		return $this->lunarInfo[$y-1900] & 0xf;
	}
	/**
	* 传回农历 y年m月的总天数
	*/
	function monthDays($y,$m) {
		return ($this->lunarInfo[$y-1900] & (0x10000>>$m))? 30: 29;
	}


	/**
	* 创建农历日期对象
	*/
	function SLunar($year,$month=1,$day=1) {
		//date from 1901-12-14 to 2038-01-19 [windows]
		//date from 1901-12-15 to 2038-01-19 [linux]
		$time = mktime(0,0,0,$month,$day,$year);
		if( $time ===false){
			return false;
		}
		$offset = round($time/86400)+25537;
		
		$this->dayCyl = $offset + 40;
		$this->monCyl = 14;

		for($i=1900; $i<$year && $offset>0; $i++) {
			$temp = $this->lYearDays($i);
			$offset -= $temp;
			$this->monCyl += 12;
		}

		if($offset<0) {
			$offset += $temp;
			$i--;
			$this->monCyl -= 12;
		}

		$this->year = $i;
		$this->yearCyl = $i-1864;
		$leap = $this->leapMonth($i); //闰哪个月

		$this->isLeap = false;
		for($i=1; $i<13 && $offset>0; $i++) {
			//闰月
			if($leap>0 && $i==($leap+1) && $this->isLeap==false) {
				$i--;
				$this->isLeap = true;
				$temp = $this->leapDays($this->year);
			}else {
				$temp = $this->monthDays($this->year, $i);
			}
			//解除闰月
			if($this->isLeap==true && $i==($leap+1))
				$this->isLeap = false;

			$offset -= $temp;
			if($this->isLeap == false)
				$this->monCyl ++;
		}

		if($offset==0 && $leap>0 && $i==$leap+1)
		if($this->isLeap)
			$this->isLeap = false;
		else {
			$this->isLeap = true;
			$i--;
			$this->monCyl--;
		}

		if($offset<0) {
			$offset += $temp;
			$i--;
			$this->monCyl--;
		}

		$this->month = $i;
		$this->day = $offset + 1;


		/*
		$days  = $this->day;

		for($i=1;$i<=$this->month;$i++){
			if($i==$this->leapMonth($this->year) &&$this->isLeap){
				$days +=$this->leapDays($this->year);
			}
			if($i!=$this->month)
				$days +=$this->monthDays($this->year,$this->month);
		}
		$this->days = $days;*/
	}

	function cyclical($num) {
		$Gan = Array("甲","乙","丙","丁","戊","己","庚","辛","壬","癸");
		$Zhi = Array("子（鼠）","丑（牛）","寅（虎）","卯（兔）","辰（龙）","巳（蛇）","午（马）","未（羊）","申（猴）","酉（鸡）","戌（狗）","亥（猪）");
		return $Gan[$num%10].$Zhi[$num%12];
	}
	/**
	* 输出，根据需要直接修改本函数或在派生类中重写本函数
	*/
	function display() {
		$nStr = array(' ','正','二','三','四','五','六','七','八','九','十','十一','腊');
		if($this->yearCyl && $this->month && $this->day)
		$nl = sprintf("%s年 %s%s月%s",$this->cyclical($this->yearCyl),($this->isLeap?"闰":""),$nStr[$this->month],$this->cDay($this->day));
		else
		$nl =null;
		//echo sprintf("农历 %s%s月%s<br>",($this->isLeap?"闰":""),$nStr[$this->month],$this->cDay($this->day));
		//echo sprintf("%s年 %s月 %s日",$this->cyclical($this->yearCyl),$this->cyclical($this->monCyl),$this->cyclical($this->dayCyl));
		RETURN $nl;
	}
	/**
	* 中文日期
	*/
	function cDay($d) {
		$nStr1 = array('日','一','二','三','四','五','六','七','八','九','十');
		$nStr2 = array('初','十','廿','卅','　');

		switch($d) {
			case 10:
			$s = '初十';
			break;
			case 20:
			$s = '二十';
			break;
			case 30:
			$s = '三十';
			break;
			default :
			$s = $nStr2[floor($d/10)];
			$s .= $nStr1[$d%10];
		}
		return $s;
	}
}  
/*test case */
/*

$ld                = new SLunar(2004,2,26);
$ld2                = new SLunar(2004,3,26);
echo $nl                = $ld->display();
echo "\n";
echo $n2                = $ld2->display();
echo "\n";
echo $ld->month.":".$ld2->month."\n";
//这是阳历日期在当年阴历中的第days天
echo $ld->days.":".$ld2->days."\n";
echo $ld->dayCyl.":".$ld2->dayCyl."\n";
//这是2个阳历的阴历相隔天数
echo "相隔天数:".($ld2->dayCyl  -  $ld->dayCyl) .":\n";
*/
?>