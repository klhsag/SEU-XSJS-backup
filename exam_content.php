<?php
session_start();

function str_validate($str){
    $str=str_replace('\\','',$str);//去掉所有反斜杠
    $str=str_replace("'","\\'",$str);//
    $str=str_replace('"','\\"',$str);
    return $str;
}

$response=[];
//response有两项,第一项是flag,值为'ok'或'fail'
//第二项是msg,当请求顺利完成时(ok),值为一个json字符串,表示考试题目
//当请求失败时(fail).值为失败信息.


require 'check_CSRF_token.php';


if(isset($_SESSION['user']) && !empty($_SESSION['user'])){
    $exam_user=$_SESSION['user'];
    require 'database_keys/testdb0802.php';
    try {
        $conn=new PDO("mysql:host=$servername;dbname=$database",$db_username,$db_password,
                       array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
        
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt=$conn->prepare("SELECT score,last_req_time FROM member WHERE username=:username");
        $stmt->bindValue(':username', $exam_user);
        $stmt->execute();
        
        $rows=$stmt->fetchAll();
        $rowCount=$stmt->rowCount();
        
        $score=$rows[0][0];
        $lastTS=$rows[0][1];
        $nowTS=date("Y-m-d H:i:s");
        $timeDiff=strtotime($nowTS)-strtotime($lastTS);
        
        require "exam_conf.php" ;
        
        if($debug_mode){
            $score=-1;
        }
        
        if($rowCount==1 && $score==-1 && $timeDiff>=$second_required_between_requests){
            //开始抽题
            $exam_data='{"choice":[';
            /* exam_data的结构
             * var exam_data=
             * {
             *  'choice':
             *  [{'question':'xxx','opt_a':'xxx',...,'opt_d':'xxx'},...],
             *  'judge':
             *  [{'question':'xxx'},...]
             * }
             * */
            
            //答案序列
            $exam_key='';
            
            //=============================选择题部分======================
            $qid_arr=array();
            
            //获取随机数组qid_arr,key从1~cnt_want的元素是1~cnt_all的不重复随机数
            for($i=1;$i<=$conf_choice_cnt;$i++){
                if(!isset($qid_arr[$i])){
                    $qid_arr[$i]=$i;
                }
                $x=mt_rand(1,$conf_choice_all);
                if(!isset($qid_arr[$x])){
                    $qid_arr[$x]=$x;
                }
                $temp=$qid_arr[$x];
                $qid_arr[$x]=$qid_arr[$i];
                $qid_arr[$i]=$temp;
            }
            
            //组题
            $stmt=$conn->prepare("SELECT body,choice_A,choice_B,choice_C,choice_D,answer 
                                  FROM qdb_choice LIMIT :qid ,1;");
            for($i=1;$i<=$conf_choice_cnt;$i++){
                $stmt->bindValue(':qid', $qid_arr[$i]-1,PDO::PARAM_INT);
                $stmt->execute();
                $rows=$stmt->fetchAll();
                $rowCount=$stmt->rowCount();
                if($rowCount==1){
                    
                    $exam_data=$exam_data.'{"question":"'.str_validate($rows[0][0])
                    .'","opt_a":"'.str_validate($rows[0][1]).'","opt_b":"'.str_validate($rows[0][2])
                    .'","opt_c":"'.str_validate($rows[0][3]).'","opt_d":"'.str_validate($rows[0][4])
                    .'"}';
                    
                    if($i<$conf_choice_cnt)
                        $exam_data.=',';
                    $exam_key.=$rows[0][5];
                }
            }
            
            $exam_data.='],"judge":[';
            //=============================判断题部分======================
            
            $qid_arr=array();
            
            //获取随机数组qid_arr,key从1~cnt_want的元素是1~cnt_all的不重复随机数
            for($i=1;$i<=$conf_judge_cnt;$i++){
                if(!isset($qid_arr[$i])){
                    $qid_arr[$i]=$i;
                }
                $x=mt_rand(1,$conf_judge_all);
                if(!isset($qid_arr[$x])){
                    $qid_arr[$x]=$x;
                }
                $temp=$qid_arr[$x];
                $qid_arr[$x]=$qid_arr[$i];
                $qid_arr[$i]=$temp;
            }
            
            
            //组题
            $stmt=$conn->prepare("SELECT body,answer FROM qdb_judge LIMIT :qid,1;");
            for($i=1;$i<=$conf_judge_cnt;$i++){
                $stmt->bindValue(':qid', $qid_arr[$i]-1,PDO::PARAM_INT);
                $stmt->execute();
                $rows=$stmt->fetchAll();
                $rowCount=$stmt->rowCount();
                if($rowCount==1){
                    
                    $exam_data=$exam_data.'{"question":"'.str_validate($rows[0][0]).'"}';
                    
                    if($i<$conf_judge_cnt)
                        $exam_data.=',';
                    $exam_key.=$rows[0][1];
                }
            }
            
            $exam_data.="]}";
            
            $response['flag']='ok';
            $response['msg']=json_encode(json_decode($exam_data),JSON_PRETTY_PRINT);
            
            if(!$debug_mode&&isset($_SESSION['exam_key'])&&!empty($_SESSION['exam_key'])){
                $response['flag']='fail';
                $_SESSION = array();
                setcookie(session_name(), '', time() - 42000);
                $response['msg']='上次考试异常中断,请退出重新登陆再考试.';
                echo json_encode($response);
                exit();
            }

            $_SESSION['exam_key']=$exam_key;
            //echo "//<br>答案:",$_SESSION['exam_key'];
            
            //更新请求时间 member表中的last_req_time 记录了上一次申请考试内容的时间
            //是一个字符串,形如 "2018-08-03 09:53:00"
            $stmt=$conn->prepare("UPDATE member SET last_req_time=:timestamp WHERE username=:username;");
            $stmt->bindValue(':username', $exam_user);
            $stmt->bindValue(':timestamp', $nowTS);
            $stmt->execute();
            
            $response['begin_time']=$nowTS;
            
        }else{
            $response['flag']='fail';
            if($score!=-1){
                $response['msg']='你已经完成考试,不得重复考试.';
            }
            if($timeDiff<$second_required_between_requests){
                $response['msg']='你在短时间内重复申请考试,请再等'
                    .($second_required_between_requests-$timeDiff)."秒后再试.";
            }
        }
        
    }
    catch(PDOException $ex){
        $response['flag']='fail';
        $response['msg']=$ex->getMessage();
    }
    
    $conn=null;
    
    
}else{
    //未登录
    $response['flag']='fail';
    $response['msg']='未登录';
}

echo json_encode($response,JSON_PRETTY_PRINT);

?>