﻿<?php
include_once 'Connection/DB.php';//connect DB
$return_message="";//回傳的HTML訊息
// $start_number=$_GET['start'];
$keyword=$_GET['keyword'];
$temp_result;
$click_number;
$return;

	
	//先找出搜尋的關鍵字
	$temp_id=$_SERVER['REMOTE_ADDR'];
	
	
	//依點擊次數排列
	$return_message="";  //清空
	$query="SELECT *
			FROM query_record
			WHERE user_id ='$temp_id'
			ORDER BY  `query_record`.`time` DESC 
			LIMIT 0,1
			";
			
			
	//echo $query;//debug
	$result = $mysqli->query($query)or die($mysqli->error.__LINE__);
	$rows=$result->num_rows;
	
	//接著找出那一個關鍵字，有哪些點擊的紀錄，按點擊次數排列並return
	//echo $rows;
	if($rows>0)
	{
		

		$return_message=$return_message."<table><tr></tr>";  //表格起始
		
		for($i=0;$i<$rows;$i++)	{
		
			// 有$rows筆搜尋紀錄
			$row=$result->fetch_array();
			$return_message = $return_message.'<tr><td>搜尋:<h2>'.$row['query'].'</h2></td><td></td><td></td></tr>';
		
			//計算次數並依照次數的大小排序
			$query="SELECT title,page,ID, count(title) 
					FROM click_record
					WHERE grk='$row[grk]'
					GROUP BY title 
					ORDER BY count(title) DESC
					LIMIT 0,4
					";
			$temp_result01 = $mysqli->query($query)or die($mysqli->error.__LINE__);
			$temp_num = $temp_result01->num_rows;  
			

				
			for($k=0; $k<$temp_num; $k++)
			{	
		
				$temp_row = $temp_result01->fetch_array();
				$click_id01 = $temp_row['ID'];
				// $temp_row['page'] = urlencode($temp_row['page']);
				
				//從外部讀圖檔
				$return_message = $return_message.'<tr><td> <img src=http://img.bitpixels.com/getthumbnail?code=4453969102875912&url='.$temp_row['page'].'/></td><td><a href="'.$temp_row['page'].'">'.$temp_row['title'].'</a>';
				
				//從資料夾讀圖檔
				// $return_message=$return_message.'<tr><td><img src=https://140.116.39.177/chrome_Thesis_m/savewebimg/'.$click_id01.'.jpg></td><td><a href="'.$temp_row['page'].'">'.$temp_row['title'].'</a>';
				
				$return_message = $return_message.'  您已造訪這個網頁 '.$temp_row['count(title)'].' 次</td><td></td></tr>';
				
				
			}//end for
		
		
		
 		}//end for
		
		$return_message = $return_message."</table>"; //表格結束
		
		//echo $return_message;
	}
	else {	
		$return_message="沒有搜尋結果";
	}
	
	// echo $return_message;
	echo $return_message;
	
// }
?>