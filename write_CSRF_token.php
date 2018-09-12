<?php 
date_default_timezone_set("PRC");
$_SESSION['csrftoken']=md5($_SESSION['user'].date('Y-m-d'));//后面是token值,无意义,够乱就行
?>
