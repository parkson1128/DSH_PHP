<?php
include_once 'Connection/DB.php';//connect DB
date_default_timezone_set('Asia/Taipei');  //校正php時區


$user_key       = $_SERVER['REMOTE_ADDR'];   //user IP
$search_query   = urldecode($_POST['keyword']);  //搜尋關鍵字
$link           = $_POST['website'];						// 連結
$click_snippet  = $_POST['websnippet'];  //點擊內容的snippet
$click_title    = $_POST['webtitle'];  //點擊內容的標題
$query_time     = $_POST['query_time'];

$click_time =  date("Y-m-d H:i:s");//當前點擊資料的時間;
$response = 0;
 
$response = record_query_click($click_title,$click_snippet,$user_key,$search_query,$link,$query_time,$click_time,$mysqli);

echo json_encode($response);



function record_query_click($click_title,$click_snippet,$user_key,$search_query,$link,$query_time,$click_time,$mysqli)
{
	//record click information
	//先找出grk資料，確認是哪一筆搜尋的紀錄
	$query="SELECT grk
			FROM query_record 
			WHERE user_id ='$user_key' AND query='$search_query' AND time='$query_time' 
			ORDER BY  `query_record`.`time` DESC 
			LIMIT 0 , 1";
			
	// echo $query."<<<\n---(回傳query_找出grk_DB資料)---\n\n";
	// echo $query_time."問題值\n\n";
	$result = $mysqli->query($query)or die($mysqli->error.__LINE__);;
	$row=$result->fetch_array();
	$grk=$row['grk'];



	//先找出click_record中最新的一筆 snippet_nohtml 以及 title資料
	//比對現在點擊的網站snippet與title資料與DB中是否相同
	//如果相同，表示是同一個網站，則不寫入DB
	//避免有人手賤狂點同一個網站連結，讓DB一直寫入，造成後續相似度比較的CKIP負擔
	$query="SELECT *
			FROM click_record 
			WHERE grk ='$grk'
			ORDER BY `time` DESC 
			LIMIT 0 , 1";
			
	// echo $query."<<<\n---(回傳query_找出grk_DB資料)---\n\n";
	// echo $query_time."問題值\n\n";
	$result = $mysqli->query($query)or die($mysqli->error.__LINE__);;
	$row=$result->fetch_array();
	$grk01=$row['grk'];
	$snippet01=$row['snippet'];
	$title01=$row['title'];
	
	if ($grk01 == $grk && $snippet01 == $click_snippet){   //同ㄧ個點擊網站，不會存入DB
		
				$response["status"] = "同個點擊網站，沒有儲存";
				return $response;
	}else{
	
		//開始儲存點擊資料
		$click_snippet_nohtml=strip_tags($click_snippet);//去除snippet裡面所有的html標籤
		// echo ">>> ".$click_snippet_nohtml." <<<\n---(回傳  click_snippet_nohtml_去除snippet裡面所有的html標籤)---\n\n";
		
		
				// 語法 mysqli_real_escape_string(connection,escapestring);
		$click_title=mysqli_real_escape_string($mysqli,$click_title);
		// echo ">>> ".$click_title." <<<\n---(回傳  click_title_mysqli_real_escape_string)---\n\n";
		
		$click_title = str_replace('_' , ' ', $click_title);  //如果有"_"字元 用空白字元替換掉 (原本ajax_search.php 有把空白變成"_",在這裡換回來) 151030新增
		
		$click_snippet=mysqli_real_escape_string($mysqli,$click_snippet);
		// echo ">>> ".$click_snippet." <<<\n---(回傳  click_snippet_mysqli_real_escape_string)---\n\n";
		
		$click_snippet_nohtml=mysqli_real_escape_string($mysqli,$click_snippet_nohtml);
		// echo ">>> ".$click_snippet_nohtml." <<<\n---(回傳  click_snippet_nohtml_轉換後_mysqli_real_escape_string)---\n\n";
		
		$click_snippet_nohtml=str_replace(array('&','║','♡','□','»','"','~','☆'),' ',$click_snippet_nohtml);  //去掉特殊符號，減少不能斷詞的情況
		
		
		/* 	  //用太多符號換底線 會讓斷詞的數量變多
		$click_snippet_nohtml=str_replace(    
								array('!', '"', '#', '$', '%', '&', '\'', '(', ')', '*',    
								'+', ', ', '-', '.', '/', ':', ';', '<', '=', '>',    
								'?', '@', '[', '\\', ']', '^' , '`', '{', '|',    
								'}', '~', '；', '﹔', '︰', '﹕', '：', '，', '﹐', '、',    
								'．', '﹒', '˙', '·', '。', '？', '！', '～', '‥', '‧',    
								'′', '〃', '〝', '〞', '‵', '‘', '’', '『', '』', '「',    
								'」', '“', '”', '…', '❞', '❝', '﹁', '﹂', '﹃', '﹄','。'),    
								'_',    
								$click_snippet_nohtml);
								 */  
								
		
		
		//簡體轉繁體    151101新增snippet轉繁體 

			
		if(1==1)
		{
			// 能echo,就不能顯示搜尋結果
			// echo "判斷出文字為簡體\n";
			// echo "原本文字為: \n".$click_snippet_nohtml."\n\n";
			
			$click_snippet_nohtml = J2F($click_snippet_nohtml);
			
			$click_title = J2F($click_title);
			
			// echo "後來轉換繁體完為:\n".$click_snippet_nohtml."\n\n";
			
			$query="SELECT id
			FROM click_record 
			WHERE grk ='$grk'
			ORDER BY `id` DESC 
			LIMIT 0 , 1";
			$result = $mysqli->query($query)or die($mysqli->error.__LINE__);
			$row = $result->fetch_array();
			$click_id01=$row['id'];
			
			
			//更新前一筆資料的 nextclicktime
			$query = "UPDATE `click_record` SET `nextclicktime` = '$click_time'  WHERE `id` = '$click_id01' ";
			$mysqli->query($query)or die($mysqli->error.__LINE__);
			
			
			
			//更新點擊紀錄
			$query="INSERT INTO click_record(grk,time,page,snippet,snippet_nohtml,title)
					VALUES('$grk','$click_time','$link','$click_snippet','$click_snippet_nohtml','$click_title')
					";
			//echo ">>>".$query."<<<---(回傳_更新點擊紀錄)---\n";
			$mysqli->query($query)or die($mysqli->error.__LINE__);
				
			//echo "  點擊資料紀錄完畢_轉換繁體";
			
			
/*			
			//查詢最新的ID是多少
			$query="SELECT id
			FROM click_record 
			WHERE grk ='$grk'
			ORDER BY `id` DESC 
			LIMIT 0 , 1";
			$result = $mysqli->query($query)or die($mysqli->error.__LINE__);
			$row = $result->fetch_array();
			$click_id01 = $row['id'];			
			
			
			
 			
			$ch = curl_init();//建立curl連線
			$s_url= 'http://img.bitpixels.com/getthumbnail?code=4453969102875912&url='.urlencode($link);
			// echo  "組合出來的網址".$s_url;
			// echo "11122255888你我他";
			curl_setopt($ch, CURLOPT_URL,$s_url);//指定網址
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//設定以文件流回傳資料
			$img = curl_exec($ch); //開始回傳資料給$html
			curl_close($ch);//關閉聯結
			
			// echo "抓回的縮圖".$img;
			
			//把字串輸出成檔案
			
			$temp = "./savewebimg/$click_id01.jpg" ;
			file_put_contents($temp, $img);			

			
			 */
			
			
			
			
			$response["status"] = "點擊資料紀錄完畢_轉換繁體成功";
		} 
		else
		{
			$query="SELECT id
			FROM click_record 
			WHERE grk ='$grk'
			ORDER BY `id` DESC 
			LIMIT 0 , 1";
			$result = $mysqli->query($query)or die($mysqli->error.__LINE__);
			$row = $result->fetch_array();
			$click_id01 = $row['id'];
			
			// $query="UPDATE `click_record` SET `nextclicktime` = '$click_time'  WHERE `id` = '$click_id01' ";
			
			//更新前一筆資料的 nextclicktime
			$query = "UPDATE `click_record` SET `nextclicktime` = '$click_time'  WHERE `id` = '$click_id01' ";
			$mysqli->query($query)or die($mysqli->error.__LINE__);
			
			
			

			
			
			
			
			
			//寫入最新的點擊紀錄
			$query="INSERT INTO click_record(grk,time,page,snippet,snippet_nohtml,title)
					VALUES('$grk','$click_time','$link','$click_snippet','$click_snippet_nohtml','$click_title')
					";
			//echo ">>>".$query."<<<---(回傳_更新點擊紀錄)---\n";
			$mysqli->query($query)or die($mysqli->error.__LINE__);
			//echo "  點擊資料紀錄完畢_沒有轉換";
			
/* 			
			//查詢最新的ID是多少
			$query="SELECT id
			FROM click_record 
			WHERE grk ='$grk'
			ORDER BY `id` DESC 
			LIMIT 0 , 1";
			$result = $mysqli->query($query)or die($mysqli->error.__LINE__);
			$row = $result->fetch_array();
			$click_id01 = $row['ID'];			
			
			
			// sleep for 10 seconds
			sleep(3);
			
			
			$ch = curl_init();//建立curl連線
			$s_url= 'http://img.bitpixels.com/getthumbnail?code=4453969102875912&url='.urlencode($link);
			// echo  "組合出來的網址".$s_url;
			// echo "11122255888你我他";
			curl_setopt($ch, CURLOPT_URL,$s_url);//指定網址
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//設定以文件流回傳資料
			$img = curl_exec($ch); //開始回傳資料給$html
			curl_close($ch);//關閉聯結
			
			// echo "抓回的縮圖".$img;
			
			$temp = "./savewebimg/$click_id01.jpg" ;
	
			
			//把字串輸出成檔案

			file_put_contents($temp, $img);		

			
			
			
			 */
			
			
			
			
			$response["status"] = "點擊資料紀錄完畢_沒有簡體字";
		}
		//簡體轉繁體結束
		
		return $response;
	
	}
	
}


function J2F($str){    //轉換成正體字
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



?>