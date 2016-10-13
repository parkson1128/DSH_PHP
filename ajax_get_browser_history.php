<?php
include_once 'Connection/DB.php';//connect DB
$return_message="";//回傳的HTML訊息
// $start_number=$_GET['start'];
$keyword=$_GET['keyword'];
$temp_result;
$click_number;
$return;

	
	
	//先找出搜尋的關鍵字
	$temp_id=$_SERVER['REMOTE_ADDR'];
	//echo "123";
 	$query="SELECT *
			FROM query_record
			WHERE user_id ='$temp_id' 
			
			ORDER BY  `query_record`.`time` DESC 
			LIMIT 0,1
			";

			
			//原本 WHERE user_id ='$temp_id' AND query='$keyword'
			
	//echo $query;//debug
	$result = $mysqli->query($query)or die($mysqli->error.__LINE__);
	$rows=$result->num_rows;
	//echo $rows;
	
	//接著找出那一個關鍵字，有哪些點擊的紀錄，按順序排列並return
	if($rows>0)
	{

		$return_message=$return_message."<table><tr></tr>";  //表格起始
		
		
		for($i=0;$i<$rows;$i++)	{
			
			//有$rows筆搜尋紀錄
			$row=$result->fetch_array();
			$return_message = $return_message.'<tr><td>搜尋:<h2>'.$row['query'].'</h2></td><td></td><td></td></tr>';
			
			//接下來針對每一筆紀錄去找其相關的點擊紀錄
			$query="SELECT * 
					FROM click_record
					WHERE grk='$row[grk]'
					ORDER BY  `click_record`.`time` DESC
					LIMIT 0,4					
					";
			$temp_result = $mysqli->query($query)or die($mysqli->error.__LINE__);
			$temp_rows=$temp_result->num_rows;			
			for($k=0;$k<$temp_rows;$k++)
			{
				$temp_row=$temp_result->fetch_array();
				$click_id01 = $temp_row['ID'];
				
				// $temp_row['page'] = urlencode($temp_row['page']);
				//開始建立新表格
				
				//從外部讀圖檔
				$return_message=$return_message.'<tr><td><img src=http://img.bitpixels.com/getthumbnail?code=4453969102875912&url='.$temp_row['page'].'/></td><td><a href="'.$temp_row['page'].'">'.$temp_row['title'].'</a></td><td>'.$temp_row['time'].'</td></tr>';
				
				//從資料夾讀圖檔
				// $return_message=$return_message.'<tr><td><img src=https://140.116.39.177/chrome_Thesis_m/savewebimg/'.$click_id01.'.jpg></td><td><a href="'.$temp_row['page'].'">'.$temp_row['title'].'</a></td><td>'.$temp_row['time'].'</td></tr>';
				
				
				// $return_message=$return_message.'<tr><td><img src=https://140.116.39.177/chrome_Thesis_m/savewebimg/test005.jpg></td><td><a href="'.$temp_row['page'].'">'.$temp_row['title'].'</a></td><td>'.$temp_row['time'].'</td></tr>';
			}
		  
			
			
 
		}
		$return_message=$return_message."</table>";//表格結束
		
		//echo $return_message;
	}
	else {	
		$return_message="沒有搜尋結果";
	} 
	

	
	echo $return_message;
	// echo $return ;
	
// }
?>