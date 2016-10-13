<?php
include_once 'Connection/DB.php';//connect DB
date_default_timezone_set('Asia/Taipei');  //校正php時區

//Variable
// $user_key= $_SESSION['id']= $_SERVER['REMOTE_ADDR'];
$user_key = $_SERVER['REMOTE_ADDR'];
$response['time'] = $query_time =  date("Y-m-d H:i:s");
$response['similarity'] = "開始比較相似度!" ;
$response['information_search'] = "沒有搜尋過，開啟輔助搜尋功能!" ;




//取得User正在搜尋的query
$response['keyword'] = $search_query = $_POST["keyword"];

$search_query = str_replace( array('+'), ' ', $search_query);  //把+號換成空白


$response['click_size'] = 0;
//設定defult value//
$new_result_flag = 0;
//設定default value//

//判斷1.是否是新使用者
//Check user record
check_user_record($user_key, $mysqli);  //假如是新使用者 ID存進資料庫
//Check user record 



if($new_result_flag == 0){	
	//假如為1 表示非新搜尋結果

	
	
	//判斷2.是否重複搜尋
	
	//(簡單說 現在DB中最新的搜尋是"蘋果"，可是馬上又重複搜尋了"蘋果"，所以不寫入 )
	//檢查USER現在查詢的字，在DB中最近一筆相同Query的查詢時間是什麼時候
	//是不是跟DB中最新的一筆 Query時間是一樣的
	//如果是，表示現在當下正在搜尋同一個關鍵字，所以不寫入  
	//如果不是，則表示這是在以前曾搜尋過同樣的關鍵字,但是現在又搜尋ㄧ次
	//所以要寫入
	//此時最新的search關鍵字還沒有寫入DB
	
	$dbOldTime = 1;
	$query="SELECT * FROM query_record 
			WHERE user_id ='$user_key' AND query='$search_query' 
			ORDER BY  `query_record`.`time` DESC 
			LIMIT 0 , 1";
	$result = $mysqli->query($query);
	$row = $result->fetch_array();
	$user_id01 = $row['user_id'];
	$Oldsearch_query = $row['query'];
	$dbOldTime = $row['time'];
	
	$array_size = count($row['query']);  //有幾個query
	

	//查詢DB中最新的一筆搜尋!!
	$query="SELECT * FROM query_record 
			WHERE user_id ='$user_key'
			ORDER BY  `query_record`.`time` DESC 
			LIMIT 0 , 1";
	$result = $mysqli->query($query);
	$row = $result->fetch_array();

	$dbNewTime = $row['time'];
	$array_size_new = count($row['query']);  
	$click_grk = $row['grk']; 
	
	if($array_size >= 1){	
	
		$response['information_search'] = 0;
	
	}
	
	
	
	
	
	
	
	
	
	// if ($user_id01 == $user_key && $Oldsearch_query == $search_query ){
		
	if($dbOldTime == $dbNewTime && $array_size_new == 1){	 //若array_size_new =0 表示使用者是第一次使用
		
		$response['log'] = "重複!Search不寫入DB";
		$response['similarity'] = 0;
		$response['time'] = $dbNewTime ;
		
			//計算點了幾次
		$query="SELECT *, count(ID)
				FROM click_record 
				WHERE grk = '$click_grk' 
				ORDER BY `time` DESC 
				LIMIT 0 , 5";

		$result = $mysqli->query($query)or die($mysqli->error.__LINE__);;
		$result = $result->fetch_array();
		// $click_size = count($result['ID']); 		
		// $response['click_size'] = $click_size ;  
		// $response['click_size'] = "111"; 	
		$response['click_size'] = $result['count(ID)']; 

	
		
	}else{
		
		$response['log'] = add_user_record($user_key,$query_time, $search_query,  $mysqli); //新增使用者query record 並回傳query時的時間
		//$query_time = strtotime($query_time);//轉格式
	
	}
	
}
else{

	//非新搜尋,故不用紀錄

}
 

echo json_encode($response);
// echo $response;

//////////////////分隔線///////////////////////





function check_user_record($user_key,$mysqli)
{
	$query='SELECT * FROM user_info WHERE user_id ="'.$user_key.'"';
	//echo $query;
	$result = $mysqli->query($query);
	//print_r($result);
	//echo $result;
	if ($result->num_rows==0)
	{
		$query="INSERT INTO user_info(user_id,name) VALUES( '$user_key','tempuser')";
		//echo $query;
		$mysqli->query($query)or die($mysqli->error.__LINE__);;//_LINE current code row numbers
	}
	else
	{
		//nothing
	}
}

function add_user_record($user_key, $query_time, $search_query, $mysqli)  //將USER的搜尋紀錄寫入DB
{
	$search_query = mysqli_real_escape_string($mysqli,$search_query);  //防止資料庫SQL Injection(資料隱碼)攻擊
	

	
	$temp_device=userAgent();
	$query="INSERT INTO query_record(user_id, time, query, device) VALUES( '$user_key','$query_time','$search_query','$temp_device')";
	$mysqli->query($query)or die($mysqli->error.__LINE__);;//_LINE current code row numbers
	$response = "Search寫入DB";
	
	return $response;
}

function J2F($str)
{
	if(trim($str)==''){
		return '';
	}
	$fstr='';
	include 'J2FData.php';
	$count=mb_strlen($str,'utf-8');
    for($i=0;$i<=$count;$i++){
        $jchar=mb_substr($str,$i,1,'utf-8');
        $fchar=isset($fantiData[$jchar])?$fantiData[$jchar]:$jchar;
        $fstr.=$fchar;
    }
    return $fstr;
}

function userAgent(){
	$ua=$_SERVER['HTTP_USER_AGENT'];
    $iphone = strstr(strtolower($ua), 'mobile'); //Search for 'mobile' in user-agent (iPhone have that)
    $android = strstr(strtolower($ua), 'android'); //Search for 'android' in user-agent
    $windowsPhone = strstr(strtolower($ua), 'phone'); //Search for 'phone' in user-agent (Windows Phone uses that)
 
 
    function androidTablet($ua){ //Find out if it is a tablet
        if(strstr(strtolower($ua), 'android') ){//Search for android in user-agent
            if(!strstr(strtolower($ua), 'mobile')){ //If there is no ''mobile' in user-agent (Android have that on their phones, but not tablets)
                return true;
            }
        }
    }
    $androidTablet = androidTablet($ua); //Do androidTablet function
    $ipad = strstr(strtolower($ua), 'ipad'); //Search for iPad in user-agent
 
    if($androidTablet || $ipad){ //If it's a tablet (iPad / Android)
        return '3';//tablet
    }
    elseif($iphone && !$ipad || $android && !$androidTablet || $windowsPhone){ //If it's a phone and NOT a tablet
        return '2';//mobile
    }
    else{ //If it's not a mobile device
        return '1';//desktop
    }
}


?>