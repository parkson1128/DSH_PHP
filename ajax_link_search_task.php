<?php
include_once 'Connection/DB.php'; //connect DB
require_once "Connection/CKIPClient.php";
date_default_timezone_set('Asia/Taipei');  //校正php時區
$time_start = microtime(true);

//need variable
//$user_id=$_GET['user_id'];//user id
//$user_id=$_SESSION['id'];//user id
$user_id= $_SERVER['REMOTE_ADDR'];
// $user_id= "140.116.39.177";
$query1="";//last query one
$query2="";//last query two
$query1_time="";//last query-one's time
$query2_time="";//last query-two's time
$snippet1="";//last query-one's time snippet
$snippet2="";//last query-two's snippet
$snippet1_number=0;//snippet number need to call
$snippet2_number=0;//snippet number need to call
$query_empty_flag=0;//check query snippet empty
$time_period = 100000000000;//default 30 min 
$similarity_threshold = 0.2;  //因反例問題修好了，所以下修評分標準
//$similarity_threshold = 0.425;  //0.425自訂，憑經驗法則  原始設定

$similarity_time = date("Y-m-d H:i:s"); //比較相似度時間

$raw_text="";//傳去給CKIP的文句
$return_text="";//ckip回傳的訊息
$return_sentences="";//CKIP回傳的句子
//$return_term="";//CKIP回傳的斷詞結果
$array_size="";//重複使用 判斷array裡有幾個元素
$fix_return1=array();//1-D array 第一個query用  ,放著每一個snippet 裡回傳的所有斷詞  用 "__"分隔每一個詞
$fix_return2=array();//同上,給第二個query用
$fix_all_return="";//all-return放著所有snippet不重複的所有字詞 沒有重複字詞出現 放進fix_all_return
$counter="";//?
$fix_return_temp=array();//暫存用
$TF1=array(array());//第一個query所有的TF值
$TF2=array(array());//第二個query所有的TF值
$IDF=array(0);//放著IDF的值,因為不管幾份文件 term IDF都會是統一一樣的
$cos_similarity=array();//cos_similarity運算會用到
$query1_jacc="";//運算jaccard coefficient 係數會用到的字串
$query2_jacc="";
$jacc_numerator=0;//運算jaccard coefficient 的分子
$jacc=0;//最後jaccard coefficient 的分數
$similarity_score=0;//最後的相似度分數
$coefficient=0.3333;//可變係數

$fix_return3=array();
$array_size01="";
$title1 = "";
$title2 = "";

$response = $_GET['keyword'];
// $response = $response.'222222';
//echo "keyword=  ".$response;



$query_empty_flag=check_query_empty($user_id,$mysqli); //確認之前的query是否為空的
$query_same_flag=query_same_judge($user_id,$mysqli); //確認之前的query是否一樣
//$query_contain_flag=query_contain_judge($user_id,$mysqli);//判斷之前的query是否有子集的概念存在
// $query_similarity_score=query_similarity_judge($user_id,$mysqli);//計算levenstein

if($query_empty_flag!=1 && $query_same_flag!=1)//條件吻合,開始計算相關性
{
		//try to get query2 and snippet1,2 //
		$query="SELECT * FROM query_record 
				WHERE user_id ='$user_id' 
				ORDER BY  `query_record`.`time` DESC 
				LIMIT 1 , 2";
		//echo "debuggggquerryyyyyyy::::".$query."\n";
		$result = $mysqli->query($query)or die($mysqli->error.__LINE__);;
		$row1=$result->fetch_array();//query1 information
		$row2=$result->fetch_array();//query2 information
		$query1=$row1['query']; //前一個關鍵字
		$query2=$row2['query']; //前二個關鍵字
		$query1_time=$row1['time'];
		$query2_time=$row2['time'];
		 // echo "前一的關鍵字_query1是=  ".$query1."\n";
		 // echo "前二的關鍵字_query2是=  ".$query2."\n";
		
		/********開始形成snippet1 的array*/
		$query="SELECT *
				FROM query_record,click_record
				WHERE query_record.user_id='$user_id' AND query_record.grk=click_record.grk AND click_record.grk='$row1[grk]'
				";
		$result = $mysqli->query($query)or die($mysqli->error.__LINE__);;
	
		$rows=$result->num_rows;
		for($t=0;$t<$rows;$t++)
		{
			$row1=$result->fetch_array();

			$snippet1 .= $row1['snippet_nohtml'];

			$title1 .= $row1['title'];
			
		}
		
		$snippet1_number = count($snippet1);
		$snippet2_number = count($snippet2);
		
		
		
		// define("CKIP_SERVER", "140.109.19.104");
		// define("CKIP_PORT", 1502);
		// define("CKIP_USERNAME", "N96024206NCKU");
		// define("CKIP_PASSWORD", "E94980018");
		
		//如果非常多次都出現相似度分數只有0.01分
		//有可能CKIP伺服器故障所以沒有斷詞
		//或者CKIP的帳號被停用，這時需要去重新啟動CKIP帳號，啟動後大概可以用6個月
		

		
		define("CKIP_SERVER", "140.109.19.104");
		define("CKIP_PORT", 1501);
		define("CKIP_USERNAME", "N96031083");
		define("CKIP_PASSWORD", "N96031083");
		
		$ckip_client_obj = new CKIPClient(
		   CKIP_SERVER,
		   CKIP_PORT,
		   CKIP_USERNAME,
		   CKIP_PASSWORD
		);
		

		//前一個關鍵字斷詞

		for($i=0; $i<1; $i++)//snippet number
		{	
			
			$raw_text=preg_replace("/(\&nbsp\;|　|\xc2\xa0)/", "", strip_tags($query2));//打保險在作一次文字處理
									//  \S 空白字元,
			//$raw_text=strip_tags($snippet1[$i]);					
			//echo "\n";
			//echo "第-1個query的 第".$i."個snippet準備送出中: \n".$raw_text."\n\n";
			
			$return_text = $ckip_client_obj->send($raw_text);
			//echo "回傳訊息_return_text=  ".$return_text."\n";
			// echo "\n\n\n";

			
			$return_term = $ckip_client_obj->getTerm();
			// echo "第-1個query的 第".$i."個Query斷詞結果是_return_term: \n";
			//print_r($return_term);//斷詞array
			

			//去掉裡面所有的 adv ,category,T
			$array_size=count($return_term);//共有幾個詞,可重複
			// echo "第-1個query_共有幾個詞,可重複= ".$array_size."\n";
			

			
			// echo "第-1個query去詞性_開始\n";
			for($j=0;$j<$array_size;$j++)//對於每個詞
			{	
				//echo "第-1個query去詞性_開始_第".$j."次\n";
				if(preg_match("/a/i",$return_term[$j]['tag']) || preg_match("/c/i",$return_term[$j]['tag']) || preg_match("/post/i",$return_term[$j]['tag'])
				   || preg_match("/adv/i",$return_term[$j]['tag'])|| preg_match("/asp/i",$return_term[$j]['tag'])|| preg_match("/M/i",$return_term[$j]['tag'])
				   || preg_match("/T/i",$return_term[$j]['tag'])|| preg_match("/P/i",$return_term[$j]['tag'])|| preg_match("/vi/i",$return_term[$j]['tag'])
				   || preg_match("/VT/i",$return_term[$j]['tag']) || preg_match("/category/i",$return_term[$j]['tag'])
				   )//刪除不必要的詞性
				{
					// echo "第-1個query沒事做_第".$j."次\n";
					//nothing  假如有以上詞性我們什麼都不作
				}
				else
				{
					

					

					// echo "第-1個query_有進來fix_return1_第".$j."次\n";
					$fix_return1[$i] = $fix_return1[$i]."__".$return_term[$j]['term'] ;//$fix_return1是1D-array  存著第i個snippet裡所有的斷詞  用"__"分隔
					//echo "第-1個query_fix_return1 第".$i."個snippet_ 第".$j."次小小詞合體= \n".$fix_return1[$i]."\n";
					
					if(false !== ($rst = stripos($fix_all_return,$return_term[$j]['term'])))//對大小寫敏感的寫法 假設有重複的字詞出現什麼都不作
					{
						// echo "第-1個query_有進來對大小寫敏感的寫法_第".$j."次\n";
						//nothing
					}
					else
					{
					
							$fix_all_return = $fix_all_return."__".$return_term[$j]['term'];//all-return放著所有snippet不重複的所有字詞
							// echo "第-1個query_fix_all_return 第".$j."次= \n".$fix_all_return."\n";
						
					}
					
				}
			}
			//sleep(5);//怕被認成server attack 所以給定休息時間
			
			
		}
		
		// echo "第一個斷詞結束,斷詞完的array是:\n";
		// print_r($fix_return1);
		// echo "\n\n\n";
		// echo "不重複詞串為:\n".$fix_all_return."\n";
		// echo "\n\n";
		
		$fix_all_return=explode("__",$fix_all_return);//將 fix_all_return 轉1D array 放著所有Query不重複字詞
		// print_r($fix_all_return);
		$array_size=count($fix_all_return);//不重複詞總共有$array_size個 
		
		// echo "snippet1=  ".$snippet1 ."\n";
		// echo "title1=  ".$title1 ."\n";
		$match_count = 0;
		for($l=0;$l<$array_size;$l++)
		{
			
			
			$match_count += $match_temp = substr_count($snippet1, $fix_all_return[$l]);
			// echo "snippet_match_temp ".$fix_all_return[$l]. " = ". $match_temp."\n";
			$match_count += $match_temp = substr_count($title1, $fix_all_return[$l]);
			// echo "title_match_temp第 ".$fix_all_return[$l]. " = ". $match_temp."\n";
			
		}
		
		
		
	

		
	// task 要串在一起
	
	if($match_count >= 1)  //通過標準组成task
	{	
		// echo "通過標準组成task\n";
		// echo "現在session裡面的資料有 pattern index= ".$_SESSION['pattern_index']."\n";
		// echo "現在session裡面的資料有 last query= ".$_SESSION['last_query']."\n";
		// echo "現在query是: ".$query1."\n";
		// echo "上一個query是: ".$query2."\n";
		if($_SESSION['pattern_index']!=0 && $_SESSION['last_query']==$query2)  //檢查是否有上一個pattern
		{
			$temp_index=$_SESSION['pattern_index'];
			//有的話去資料庫撈出上一個pattern 並接在之後
			$query="SELECT * FROM pattern 
					WHERE user_id ='$user_id' AND index_number='$temp_index' ";
			//echo "debug::".$query."\n";
			$result = $mysqli->query($query);
			$row_temp=$result->fetch_array();
			$pattern=$row_temp['pattern'].",".$query1;
			$pattern_time=$row_temp['pattern_query_time'].','.$query1_time;
			
			$task_result = $row_temp['task_result'];
			if ($task_result != "No" ){
			
				$query="UPDATE pattern
						set pattern='$pattern',pattern_query_time='$pattern_time',time='$query1_time', task_result='check'
						WHERE user_id ='$user_id' AND index_number='$temp_index'
						";
				//echo "debug".$query."\n";
				$mysqli->query($query)or die($mysqli->error.__LINE__);
				$_SESSION['last_query']=$query1;
				// echo "更新pattern加進DB\n";
			
			}else{
				
				$pattern=$query2.','.$query1;
				$pattern_time=$query2_time.','.$query1_time;
				$query="INSERT INTO pattern(user_id,pattern,pattern_query_time,time,task_result) VALUES('$user_id','$pattern','$pattern_time','$query1_time','check')";
				$mysqli->query($query)or die($mysqli->error.__LINE__);;//_LINE current code row numbers
				//record pattern index for next comparison
				$query="SELECT * FROM pattern
						WHERE user_id ='$user_id' AND pattern='$pattern' 
						ORDER BY `time` DESC 
						LIMIT 0 , 1";
				$result = $mysqli->query($query);
				$row_temp=$result->fetch_array();
				$_SESSION['pattern_index']=$row_temp['index_number'];
				$_SESSION['last_query']=$query1;
				
			}
			
		}
		else
		{
			$pattern=$query2.','.$query1;
			$pattern_time=$query2_time.','.$query1_time;
			$query="INSERT INTO pattern(user_id,pattern,pattern_query_time,time,task_result) VALUES('$user_id','$pattern','$pattern_time','$query1_time','check')";
			$mysqli->query($query)or die($mysqli->error.__LINE__);;//_LINE current code row numbers
			//record pattern index for next comparison
			$query="SELECT * FROM pattern
					WHERE user_id ='$user_id' AND pattern='$pattern' 
					ORDER BY `time` DESC 
					LIMIT 0 , 1";
			$result = $mysqli->query($query);
			$row_temp=$result->fetch_array();
			$_SESSION['pattern_index']=$row_temp['index_number'];
			$_SESSION['last_query']=$query1;
			// echo "新pattern加進DB\n";
			//沒有直接組合成新pattern
		}
		// echo "經過處理後現在session裡面的資料有 pattern index=".$_SESSION['pattern_index']."\n";
		// echo "經過處理後現在session裡面的資料有 last query=".$_SESSION['last_query']."\n";
	}
	else//沒有通過標準
	{
		//重設session
		// echo "不相關拉~~\n\n";
		$_SESSION['pattern_index']=0;
	}
	//print_r($TF);

}
else
{
	$_SESSION['pattern_index']=0;
	echo "條件不符，沒有進行 link_task \n";
	
}
$time_end = microtime(true);
$time = $time_end - $time_start;
// echo "花費時間= ".$time;

// echo "match_count=  ". $match_count;
echo $match_count;

function time_judge($user_key,$time_period,$mysqli)
{
	$query="SELECT * FROM query_record 
			WHERE user_id ='$user_key' 
			ORDER BY  `query_record`.`time` DESC 
			LIMIT 1 , 2";
	//echo "time_judge".$query."\n";
	$result = $mysqli->query($query);
	$row1=$result->fetch_array();
	$row2=$result->fetch_array();
	// echo $row1['time']."\n";
	// echo $row2['time']."\n";
	// echo strtotime($row1['time'])."\n";
	$time_diff=strtotime($row1['time'])-strtotime($row2['time']);
	//echo $time_diff;
	//echo $row['time'];
	
	//algorithm begin 
	//Fist check time period
	if($time_diff>$time_period)
	{
		return 0;
	}
	else
	{
		$q_array=array($row1['query'],$row2['query']);
		return $q_array;
	}	
}


function query_same_judge($user_key,$mysqli)
{
	$query="SELECT * FROM query_record
			WHERE user_id ='$user_key'
			ORDER BY `time` DESC 
			LIMIT 1 , 2";
	$result = $mysqli->query($query);
	$row1=$result->fetch_array();
	$row2=$result->fetch_array();
	if($row1['query']==$row2['query'])
	{
		$flag=1;
	}
	else
	{
		$flag=0;
	}
	return $flag;
}


function query_contain_judge($user_key,$mysqli)
{
	$flag=0;
	$query="SELECT * FROM query_record
			WHERE user_id ='$user_key'
			ORDER BY `time` DESC 
			LIMIT 1 , 2";
	$result = $mysqli->query($query);
	$row1=$result->fetch_array();
	$row2=$result->fetch_array();
	$temp_number1=mb_strlen($row1['query']);
	$temp_number2=mb_strlen($row2['query']);
	$temp_row2_string=$row2['query'];
	$temp_row1_string=$row1['query'];
	if($temp_number1>=$temp_number2){
		if(preg_match("/$temp_row2_string/",$temp_row1_string))
		{
		 $flag=1;
		}
	}else{
		if(preg_match("/$temp_row1_string/",$temp_row2_string))
		{
		 $flag=1;
		}
	}
	return $flag;
}

function query_similarity_judge($user_key,$mysqli)
{
	
	$query="SELECT * FROM query_record
			WHERE user_id ='$user_key'
			ORDER BY `time` DESC 
			LIMIT 1 , 2";
	$result = $mysqli->query($query);
	$row1=$result->fetch_array();
	$row2=$result->fetch_array();
	similar_text($row1['query'],$row2['query'],$percent);
	$percent=$percent/100;
	return $percent;
}



function query_time_function($user_key,$search_query,$mysqli)
{
	//$query="INSERT INTO query_record(user_id,query) VALUES( '$user_key','$search_query')";
	//$mysqli->query($query)or die($mysqli->error.__LINE__);;//_LINE current code row numbers
	$query="SELECT * FROM query_record 
			WHERE user_id ='$user_key' AND query='$search_query' 
			ORDER BY  `query_record`.`time` DESC 
			LIMIT 0 , 1";
	$result = $mysqli->query($query);
	$row1=$result->fetch_array();
	//echo $row1['time']."使用者時間\n";
	return $row1['time'];
}


function check_query_empty($user_key,$mysqli)
{
	$query="SELECT * FROM query_record 
			WHERE user_id ='$user_key'
			ORDER BY  `query_record`.`time` DESC 
			LIMIT 1 , 2";
	$result = $mysqli->query($query);
	//找出前兩個query的grk
	$row1=$result->fetch_array();
	$row2=$result->fetch_array();
	//確認前兩個query是否沒有點擊紀錄
	$query="SELECT COUNT(*)
			FROM click_record
			WHERE click_record.grk='$row1[grk]'
			";
	$result = $mysqli->query($query);
	$row1=$result->fetch_array();
	
	$query="SELECT COUNT(*)
			FROM click_record
			WHERE click_record.grk='$row2[grk]'
			";
	$result = $mysqli->query($query);
	$row2=$result->fetch_array();
	
	if($row1['COUNT(*)']=="0"||$row2['COUNT(*)']=="0")
	{
		$flag=1;
	}
	else
	{
		$flag=0;
	}
	return $flag;
}

?>