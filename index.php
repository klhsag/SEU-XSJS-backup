<!DOCTYPE html>
<?php
session_start();

require 'db_init.php';

if($GLOBALS['resFlag']!='ok'){
    echo $GLOBALS['errorMsg'];
    exit();
}

/*
echo "<br>==========php代码测试============<br>";
//在这儿测试一些后端语句比较方便
echo "以下为测试内容...<br>";
echo "sid=",session_id(),"<br>";

echo "<br>==============================<br>";
*/

$response=[];
//response是个数组, 其值将会作为json传递给index.html, 下面将会为它合理地赋值

if(isset($_SESSION['user']) && !empty($_SESSION['user'])){
    //var_dump($_SESSION); echo "<br>";          //测试session, 观察用户信息的正确性

    $response['flag']='already_login';
    $response['username']=$_SESSION['user'];
    //接下来给response['score']赋值,score的值不应从SESSION中取(否则有漏洞),应从数据库中查询

    require 'database_keys/testdb0802.php';

    try {
        $conn=new PDO("mysql:host=$servername;dbname=$database",$db_username,$db_password);
        
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt=$conn->prepare("select score FROM member WHERE username=:username");
        $username=trim($response['username']);
        $stmt->bindParam(':username', $username);
        
        $stmt->execute();
        
        $rows=$stmt->fetchAll();
        $rowCount=$stmt->rowCount();
        
        if($rowCount==1){
        	$response['score']=$rows[0]['score'];
        }
    }
    catch(PDOException $ex){
        $response['flag']='fail';
        $response['msg']=$ex->getMessage();
    }
    $conn=null;
}else{
    $response['flag']='not_login';
}

echo "<script>var pageArgsStr='",json_encode($response),"';</script>";

require 'view/index.html';

?>
