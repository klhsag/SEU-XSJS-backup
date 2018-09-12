<?php

session_start();

if(isset($_SESSION['user']) && !empty($_SESSION['user'])){
    if(preg_match('/^admin(\w{2})$/', $_SESSION['user'] , $reg)){
    	//$reg[1]是院系编号
        echo "<script> var php_department_id='",$reg[1],"';</script>";
        require 'view/department.html';
    }
}

?> 