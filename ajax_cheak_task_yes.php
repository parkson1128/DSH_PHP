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
$temp_user_id="";//暫時存放USER的id
$pattern_query_time="";//放著從row裡面讀出來的pattern資料
$return_html="";//回傳html剛開始
$array_size="";//存放array的大小數目
$result_temp="";//怕與第一個回傳衝突所以有第二個,存放sql回傳的結果
$row_temp="";//怕與第一個回傳衝突所以有第二個,存放一筆資料
$temp_class_name="";//回傳html 的class名稱 dynamic變動
$pattern_flag=0;//為了比對並找到下一個query用的flag
$temp_number=0;//為了讓class name正確暫時使用的變數
$temp_link="";//連結FB使用者的個人頁面

//點擊相關的資訊
$click_page ="";//切開pattern
$click_snippet = "";//切開pattern
$click_title = "";//切開pattern
$click_size = "";//有幾個click
//點擊相關的資訊

$taskid = $_GET['taskid'];
$nofind=0;
$newpage = "_blank";


$return;


	// 當使用者按下 有關係的按鈕後
	// 在資料庫寫入 YES

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
		$query="UPDATE pattern
			set task_result='Yes'
			WHERE user_id ='$user_id' AND index_number='$taskid'
			";
		$mysqli->query($query)or die($mysqli->error.__LINE__);	
		
	
	}
	
echo "task_result=Yes";
	


?>
