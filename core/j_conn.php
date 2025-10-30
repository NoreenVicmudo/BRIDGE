<?php 
$server = "localhost";
$user = "root";
$pass = "";
$db = "db_capstone";
date_default_timezone_set("Asia/Manila");

try {
	$con = new PDO("mysql:host=$server;dbname=$db", $user, $pass);
	$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
	echo "Connection failed: ".$e->getMessage();
}
?>