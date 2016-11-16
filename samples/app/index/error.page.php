<?php
class index_error{
	public function pageEntry($inPath){
		/*
		 */
		SError::$CONSOLE = true;
		/*
		 */
		SError::$LOG = false;
		/*
		 */
		SError::$LOGFILE="/tmp/tmp_serror.log";



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
	}
}
