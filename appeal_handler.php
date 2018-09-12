<?php

$response=array();

if($_SERVER['REQUEST_METHOD']=='GET'){
    $response['err']='method_get';

}else if($_SERVER['REQUEST_METHOD']=='POST'){

	$response['type'] = $_POST["type"];
	//$response['allow'] = 'false';
	//$response['content'] = '请等待我们的处理';
	$response['msg'] = "开始之前";

	try{
		require 'database_keys/testdb0802.php';

		$conn=new PDO("mysql:host=$servername; dbname=$database; charset=utf8", $db_username, $db_password);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		//开始查询数据库
		$stmt=$conn->prepare("SELECT * FROM appeals WHERE name=:name");
		$stu_name=trim($_POST["stu_name"]);
		$stmt->bindParam(':name', $stu_name);
		$stmt->execute();

		$rows=$stmt->fetchAll();
		$rowCount=$stmt->rowCount();

		$response['msg'] = "未开始";

		if ($rowCount==0){                     //新建一条记录
			$stmt = $conn->prepare("SELECT allowed FROM appeals WHERE name='std'");
			$stmt->execute();
			$rows=$stmt->fetchAll();
			if ($stmt->rowCount()==0) {
				throw new PDOException();
			}

			$cur_ids =  $rows[0]['allowed'] +1;

			$stmt=$conn->prepare("UPDATE appeals SET allowed=:allowed WHERE name='std';");
            $stmt->bindValue(':allowed', $cur_ids);
            $stmt->execute();

            $tmp_content = "";

            $stmt = $conn->prepare("INSERT INTO appeals(id, name, content) VALUES (:id, :name, :content)");
            $stmt->bindParam(':id', $cur_ids);
            $stmt->bindParam(':name', $stu_name);
            $stmt->bindParam(':content', $tmp_content);
			$stmt->execute();
		}
		
		//操作存在的记录
		if ($_POST['type']=='appeal' &&  $rows[0]['allowed']>0){
			$rows[0]['allowed'] = $rows[0]['allowed']-1;
			$stmt = $conn->prepare("UPDATE appeals SET allowed=:allowed,content=:content WHERE name=:name;");
            $stmt->bindParam(':name', $stu_name);
            $stmt->bindParam(':allowed', $rows[0]['allowed']);
            $stmt->bindParam(':content', $_POST['content']);
			$stmt->execute();
		}

		//刷新内容
		$stmt = $conn->prepare("SELECT allowed,content FROM appeals WHERE name=:name;");
		$stmt->bindParam(':name', $stu_name);
		$stmt->execute();
		$rows=$stmt->fetchAll();
		if ($stmt->rowCount()==0){
			$response['allow'] = 'false';
			$response['content'] = 'Error..';
		}else{
			$response['content'] = $rows[0]['content'];
			if ($rows[0]['allowed']==0) {
				$response['allow'] = 'false';
			}else{
				$response['allow'] = 'true';
			}
		}
		
		$response['msg'] = "数据库操作结束";

		$response['name'] = $stu_name;

		$conn=null;
    }
	catch(PDOException $ex){
		$response['err']="PDO".$ex->getMessage();
	}


}
echo json_encode($response);

?>
