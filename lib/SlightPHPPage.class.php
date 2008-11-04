<?php
class SlightPHPPage{
	/*
	private $data;
	public function page(&$data){
		$this->data=$data;
	}
	*/
	public static function _to($url,$pageNo){
		if(stripos($url,":page:")!==false){
			return str_ireplace(":page:",$pageNo,$url);
		}else{
			return sprintf($url,$pageNo);
		}
	}
	public static function toPage(&$data,$url,$limit=12,$select=true){
		$page="";
		if($limit%2!=0){$limit+=1;}
		$index=1;
		if($data->page>$limit/2){
				$tmp=page::_to($url,1);
				$page.=" <a title='第 1 页' href='$tmp'>|&lt;</a> ";
				$p  = $data->page - $limit/2;
				$tmp=page::_to($url,$p);
				$page.=" <a title='第 $p 页' href='$tmp'>&lt;&lt;</a> ";
		}
		for($i=1;$i<$limit/2;$i++){
			if($data->page - ($limit/2-$i) >0){
				$p  = $data->page - ($limit/2-$i);
				$tmp=page::_to($url,$p);
				$page.=" <a title='第 $p 页' href='".($tmp)."'>$p</a> ";
				if(++$index>$limit){break;}
			}
		}
		$page.= $data->page;
		for($i=1;$i<$limit/2;$i++){
			if($data->totalPage >= $data->page +$i){
				$p  =$data->page + $i;
				$tmp=page::_to($url,$p);
				$page.=" <a title='第 $p 页' href='".($tmp)."'>$p</a> ";
				if(++$index>$limit){break;}

			}
		}
		if($data->page+1<=($data->totalPage-$limit/2)){
				$p  = ($data->page +$limit/2);
				$tmp=page::_to($url,$p);
				$page.=" <a title='第 $p 页' href='$tmp'>&gt;&gt;</a>";
				$p  = $data->totalPage;
				$tmp=page::_to($url,$p);
				$page.=" <a title='第 $p 页' href='$tmp'>&gt;|</a>";
		}
		if($data->totalPage>1){
			$page.=" 第 <select onchange='window.location=this.options[selectedIndex].value'>";
			for($i=1;$i<=$data->totalPage;$i++){
				$tmp=page::_to($url,$i);
				$page.="<option value='$tmp'";
				if($i==$data->page){
					$page.=" selected ";
				}
				$page.="/>$i";
			}
			$page.="</select> 页";
		}
		return $page;
	}

}
?>
