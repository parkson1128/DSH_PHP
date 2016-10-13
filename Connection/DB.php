<?php
if(!isset($_SESSION)) {
     session_start();
}
 //print_r($_SESSION);

$dbhost  = '127.0.0.1';    // Unlikely to require changing
$dbname  = 'rebuild_system';       // Modify these...
$dbuser  = 'weihao';   // ...variables according
$dbpass  = 'weihao';   // ...to your installation

$mysqli = new mysqli($dbhost, $dbuser, $dbpass,$dbname);
if ($mysqli->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}
//mysql_query('SET NAMES utf8');
$mysqli->query('SET NAMES utf8');
//mysql_query('SET CHARACTER_SET_CLIENT=utf8');
$mysqli->query('SET CHARACTER_SET_CLIENT=utf8');
//mysql_query('SET CHARACTER_SET_RESULTS=utf8');
$mysqli->query('SET CHARACTER_SET_RESULTS=utf8');
/////////
/*
$mysqli_one = new mysqli($dbhost, $dbuser, $dbpass,'TA');
if ($mysqli_one->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}
//mysql_query('SET NAMES utf8');
$mysqli_one->query('SET NAMES utf8');
//mysql_query('SET CHARACTER_SET_CLIENT=utf8');
$mysqli_one->query('SET CHARACTER_SET_CLIENT=utf8');
//mysql_query('SET CHARACTER_SET_RESULTS=utf8');
$mysqli_one->query('SET CHARACTER_SET_RESULTS=utf8');
*/
?>