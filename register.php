<?php

session_start();

if(isset($_SESSION['user'])&&!empty($_SESSION['user'])&&preg_match('/^admin(\w{2})$/', $_SESSION['user'] , $reg)){
	echo "<script>var csrftoken='",$_SESSION['csrftoken'],"';</script>";
    require "view/register.html";
}else{
    $_SESSION = array();
    setcookie(session_name(), '', time() - 42000);
    echo "无权限.";
}

//echo "<script></script>";
?>