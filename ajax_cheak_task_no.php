<?php
include_once 'Connection/DB.php';//connect DB
// $user_key=$_SESSION['id'];
// $user_key=$_SERVER['REMOTE_ADDR'];
$user_id=$_SERVER['REMOTE_ADDR'];
// $search_query=$_GET['keyword'];
$search_query = "";
$pattern_index=$_SESSION['pattern_index'];//先比較pattern
$query="";//sql 語法
$result="";//sql回傳結果
$rows="";//sql回傳結果有幾列
$row="";//取出一列sql回傳結果
$last_pattern="";//上一次形成pattern(如果有)
$last_pattern_array_size="";//上一次pattern的長度
$pattern="";//存放類似的pattern
$pattern_query_time="";//放著從row裡面讀出來的pattern資料
$array_size="";//存放array的大小數目





$taskid = $_GET['taskid'];



$return;

//當使用者按下"沒關係"之後，
//會把資料庫中已經連在一起的Task 分開切斷!


		//找出task

		$query="SELECT * FROM pattern
				WHERE user_id ='$user_id' AND index_number='$taskid'
				ORDER BY  `pattern`.`time` DESC 
				LIMIT  0, 1
				";		

		$result = $mysqli->query($query)or die($mysqli->error.__LINE__);;
		$rows=$result->num_rows;	

		// echo "rows有幾個=  ".$rows."\n";

		if($rows > 0)
		{
			for($i=0;$i<$rows;$i++)   //有相關的pattern有好幾串
			{
				$row = $result->fetch_array();
				
				$pattern = $row['pattern'];
				$pattern = explode(",",$pattern); //切開pattern->變成array
				
				
				$pattern_query_time = $row['pattern_query_time'];   //query出原始的點擊資料時會用到
				$pattern_query_time = explode(",",$pattern_query_time); //切開search time->變成array

				$array_size = count($pattern);  //有幾個query
				
				// $task_id = $row['index_number'];
				// $return[2] = $task_id ;

				
				//抓出最後一個,並分離
				$return[1] = $pattern[$array_size-1];
				// echo "最後一個= ".$pattern[$array_size-1]."\n";
				
				$other_pattern = explode(",".$pattern[$array_size-1],$row['pattern']);
				$return[0] = $other_pattern[0];
				// echo "前面幾個= ".$other_pattern[0]."\n";  //不包含最後一個關鍵字
				
				$return[4] = $pattern_query_time[$array_size-1];
				
				$other_pattern = explode(",".$pattern_query_time[$array_size-1],$row['pattern_query_time']);
				$return[3] = $other_pattern[0];
				
				
				$query="UPDATE pattern
				set pattern='$return[0]', pattern_query_time='$return[3]', task_result='No'
				WHERE user_id ='$user_id' AND index_number='$taskid'
				";
				$mysqli->query($query)or die($mysqli->error.__LINE__);	
				
				
				
				
				
			}	//end for
			
		}else{
			
			
			
		} //end else




	

echo "task_result=No";
// echo json_encode($return);


?>
