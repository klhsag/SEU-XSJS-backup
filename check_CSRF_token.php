<?php
if(isset($_SESSION['csrftoken'])&&!empty($_SESSION['csrftoken'])){
	require 'patch_get_headers.php';
    $headers = apache_request_headers();
    if(isset($headers['CSRF-TOKEN'])){
    	if(md5($headers['CSRF-TOKEN'])!=md5($_SESSION['csrftoken'])){
    		echo "token error";
        	exit();
   		 }
    }else{
    	echo "token not found";
    	exit();
    }
}
?>