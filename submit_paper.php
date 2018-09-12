<?php
session_start();
/*
 * res结构:
 * res={'type':'xxxx','score':xxxx,'wrongs':[x1,x2,x3,...]}
 * */
require 'exam_conf.php';
$response=array();

require 'check_CSRF_token.php';

if(isset($_SESSION['user']) && !empty($_SESSION['user'])){
    //已登录
    if(!isset($_SESSION['exam_key']) || empty($_SESSION['exam_key'])){
        $response['flag']='fail';
        $response['msg']='上次考试异常中断,请退出重新登录再考试.';
        echo json_encode($response);
        exit();
    }
    
    $exam_user=$_SESSION['user'];
    require 'database_keys/testdb0802.php';
    try {
        $conn=new PDO("mysql:host=$servername;dbname=$database",$db_username,$db_password,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
        
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt=$conn->prepare("SELECT score,last_req_time FROM member WHERE username=:username");
        $stmt->bindParam(':username', $exam_user);
        $stmt->execute();
        
        $rows=$stmt->fetchAll();
        $rowCount=$stmt->rowCount();
        
        $lastTS=$rows[0][1];
        $nowTS=date("Y-m-d H:i:s");
        $timeDiff=strtotime($nowTS)-strtotime($lastTS);
        
        if($timeDiff<$second_required_before_submit){
            $response['flag']='fail';
            $response['msg']='请认真答题,还有'.($second_required_before_submit-$timeDiff)."秒才能交卷.";
            echo json_encode($response);
            exit();
        }
        
        
        if($timeDiff>($conf_time_limit)){
            $response['flag']='fail';
            $response['msg']='你交卷超时'.($timeDiff-$conf_time_limit)."秒,请刷新页面重新考试.";
            echo json_encode($response);
            exit();
        }
        
        $db_score=$rows[0][0];
        if($debug_mode){
            $db_score=-1;
        }

        if($rowCount==1 && $db_score==-1){
            //算分
            $examKey=$_SESSION['exam_key'];
            $uAnswer=$_GET['ans'];
            $qcnt=strlen($uAnswer);
            if($qcnt!=strlen($examKey)){
                $response['flag']='fail';
                $response['msg']='试卷提交失败!错误信息:答案个数与试卷不匹配.';
                echo json_encode($response);
                exit();
            }
            
            $wrongs=array();
            $score=100;
            
            for($i=0;$i<$qcnt;$i++){
                if($uAnswer[$i]!=$examKey[$i]){
                    if(($i+1)<=$conf_choice_cnt){
                        //选择题
                        $score-=$conf_choice_score;
                    }else{
                        //判断题
                        $score-=$conf_judge_score;
                    }
                    array_push($wrongs, array($i+1,$examKey[$i]));
                }
            }
            
            $response['flag']='ok';
            $response['score']=$score;
            $response['wrongs']=$wrongs;
            echo json_encode($response);
            //向数据库写入分数
            $stmt=$conn->prepare("UPDATE member SET score=:score WHERE username=:username");
            $stmt->bindParam(':username', $exam_user);
            $stmt->bindParam(':score',$score,PDO::PARAM_INT);
            $stmt->execute();
            
        }else{
            $response['flag']='fail';
            $response['msg']='试卷提交失败!错误信息:你已经完成考试,不得重复考试.';
            echo json_encode($response);
            exit();
        }
    }
    catch(PDOException $ex){
        $response['flag']='fail';
        $response['msg']=$ex->getMessage();
        echo json_encode($response);
        exit();
    }
    
    $conn=null;

}else{
    //未登录
    $response['flag']='fail';
    $response['msg']='试卷提交失败!错误信息:考生未登录.';
    echo json_encode($response);
    exit();
}

?>