<?php
session_start();
$response=[];

if(isset($_SESSION['user']) && !empty($_SESSION['user'])){
    if(preg_match('/^admin(\w{2})$/', $_SESSION['user'] , $reg)){
    	//$reg[1]是院系编号
    	getDataFromDepartment($reg[1]);
    }else{
    	$response['flag']='fail';
    	$response['msg']='你不是管理员,不能查询!';
    }
}else{
	$response['flag']='fail';
	$response['msg']='查询前请先登录.';
}

echo json_encode($response);

function getDataFromDepartment($depa_id){
	global $response;
	$response['gdata']=[];
    $response['stat']=[];
	require 'database_keys/testdb0802.php';
    try {
        $conn=new PDO("mysql:host=$servername;dbname=$database",$db_username,$db_password);
        
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt=$conn->prepare("SELECT password,score FROM member 
        	WHERE department_id=:did AND username!=:uname");

        $stmt->bindValue(':did', $depa_id);
        $stmt->bindValue(':uname',$_SESSION['user']);
        
        $stmt->execute();
        
        $rows=$stmt->fetchAll();
        foreach ($rows as $key => $value) {
        	$response['gdata'][$key] = [ $value['password'], $value['score'] ];
        }

        //===========================下面查所有院系整体数据=======================

        $stmt=$conn->prepare("SELECT update_time FROM department LIMIT 0,1;");
        $stmt->execute();
        $rows=$stmt->fetchAll();

        $lastTS=$rows[0]['update_time'];
        $nowTS=date("Y-m-d H:i:s");

        $timeDiff=strtotime($nowTS)-strtotime($lastTS);

        if($timeDiff>60){
            //更新department表
            $stmt=$conn->prepare("SELECT department_id,
                count(*)-1 AS total,
                SUM(if(score!=-1,1,0)) AS done,
                SUM(if(score>=60,1,0)) AS pass,
                SUM(if(score!=-1,score,0)) AS total_score
                FROM member GROUP BY department_id;");
            $stmt->execute();
            $rows=$stmt->fetchAll();

            $stmt=$conn->prepare("DELETE FROM department;");
            $stmt->execute();
            $stmt=$conn->prepare("INSERT INTO department (id,update_time,average,rate,pass_rate) VALUES(:id,:utime,:av,:rate,:pass);");
            foreach ($rows as $key => $value) {
                $stmt->bindValue(':id',$value['department_id']);
                $stmt->bindValue(':utime',$nowTS);
                $stmt->bindValue(':av',
                    $value['done']==0?0:
                    $value['total_score']/$value['done']);
                $stmt->bindValue(':rate',
                    $value['total']==0?0:
                    ($value['done']/$value['total'])*100);
                $stmt->bindValue(':pass',
                    $value['done']==0?0:
                    ($value['pass']/$value['done'])*100);


                $stmt->execute();
            }
        }

        //=========查department表并返回
        $stmt=$conn->prepare("SELECT id,average,rate,pass_rate FROM department;");
        $stmt->execute();
        $rows=$stmt->fetchAll();

        foreach ($rows as $key => $value) {
            $response['stat'][$key]=array($value['id'],$value['average'],$value['rate'],$value['pass_rate']);
        }


        $response['flag']='ok';
        $conn=null;
    }
    catch(PDOException $ex){
    	global $response;
        $response['flag']='fail';
        $response['msg']=$ex->getMessage();
    }
}

?>