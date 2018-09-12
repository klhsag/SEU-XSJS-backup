<?php

session_start();
$response=array();

if(isset($_SESSION['user'])&&!empty($_SESSION['user'])&&preg_match('/^admin(\w{2})$/', $_SESSION['user'] , $reg)){
	require 'check_CSRF_token.php';

	$username = trim($_POST["username"]);
    $pwd = trim($_POST["password"]);
    $response['username']=$username;
    $response['password']=$pwd;
    if(empty($username)||empty($pwd)){
    	$response['flag']='fail';
    	$response['msg']='信息不能为空!';
    	echo json_encode($response);
    	exit();
    }


	require 'database_keys/testdb0802.php';
	try {
        $conn = new PDO("mysql:host=$servername; dbname=$database", $db_username, $db_password);
        
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


        if($_POST['force']==1){

        	//查
        	$stmt = $conn->prepare("SELECT username FROM member WHERE username=:username");
      	  	$stmt->bindParam(':username', $username);
        	$stmt->execute();
        	$rows=$stmt->fetchAll();
        	$rowCount=$stmt->rowCount();
        	if($rowCount){
        		//有
        		$stmt = $conn->prepare("UPDATE member SET password=:p,department_id=:d WHERE username=:u");
	        	$stmt->bindParam(':u', $username);
	        	$stmt->bindParam(':p', $pwd);
	        	$stmt->bindParam(':d', $reg[1]);
	        	$stmt->execute();
        	}else{
        		//没有
        		//新建
	        	$stmt = $conn->prepare("INSERT INTO member (username,password,department_id) VALUES (:u,:p,:d)");
	        	$stmt->bindParam(':u', $username);
	        	$stmt->bindParam(':p', $pwd);
	        	$stmt->bindParam(':d', $reg[1]);
	        	$stmt->execute();
        	}

        	//记录
        	$stmt = $conn->prepare("INSERT INTO admin_log (admin,studentcard,studentnum) VALUES (:a,:c,:n)");
        	$stmt->bindParam(':a', $_SESSION['user']);
        	$stmt->bindParam(':c', $username);
        	$stmt->bindParam(':n', $pwd);
        	$stmt->execute();
        	$response['flag']='ok';
        	$response['msg']="学生信息注册成功!用户名:".$username.",密码:".$pwd.",院系编号:".$reg[1].".";
        }else{
        	//查
        	$stmt = $conn->prepare("SELECT username,password,department_id FROM member WHERE username=:username");
      	  	$stmt->bindParam(':username', $username);
        	$stmt->execute();
        	$rows=$stmt->fetchAll();
        	$rowCount=$stmt->rowCount();
        	if($rowCount){
        		$response['found']='yes';
        		$response['oldpassword']=$rows[0]['password'];
        		$response['did']=$rows[0]['department_id'];
        	}else{
        		$response['found']='no';
        	}
        	$response['flag']='ok';
        }
        
        $conn = null;
    }
    catch(PDOException $ex){
    	$response['flag']='fail';
    	$response['msg']=$ex->getMessage();
    }
}

echo json_encode($response);

?>