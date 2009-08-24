<?php
require_once("global.php");


$index = "test";
{
	//$opts["exact_phrase"] = $exact;
	#print "exact_phrase=$exact\n";

	$cl = new SSphinx ();
	$cl->SetServer("127.0.0.1",3312);
	//$cl->SetConnectTimeout(5);
	$cl->SetArrayResult(true);
	//print_r($keywords = $cl->BuildKeywords ( "this.is.my query", "test", false ));
	//print_r($keywords = $cl->BuildKeywords ( "拉动内需政策以", "test", false ));
	$cl->setPage(1);
	$cl->setLimit(1);
	//$res = $cl->BuildExcerpts ( $docs, $index, $words, $opts );
	//$cl->SetMatchMode(SPH_MATCH_ALL);
	//$res = $cl->Query ( "test", $index );
	//$res = $cl->Query ( "test", $index );
	//print_r($res);
	$res = $cl->Query ( "中国", $index );
	print_r($res);
	$res = $cl->Query ( "拉动内需政策以", $index );
	print_r($res);
	//$res = $cl->Query ( "中", $index );
	//print_r($res);
	
}

//
// $Id: test2.php 910 2007-11-16 11:43:46Z shodan $
//

?>