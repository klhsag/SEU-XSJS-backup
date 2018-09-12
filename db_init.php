<?php
require 'database_keys/testdb0802.php';

require 'password-encrypt.php';

$GLOBALS['resFlag']='start';
$GLOBALS['errorMsg']='none';
try {
    $conn=new PDO("mysql:host=$servername; dbname=$database", $db_username, $db_password,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //===================================================
    //查xsjs_dbseed表,没有则创建
    //这个表就一行一列,只记录一个版本信息
    $v_new='18_0830b';

    $stmt=$conn->prepare("SELECT table_name FROM information_schema.tables
                                            WHERE table_name='xsjs_dbseed'");
    $stmt->execute();
    $resCnt=$stmt->rowCount();
    
    if($resCnt==0){
        $stmt=$conn->prepare("CREATE TABLE xsjs_dbseed (version VARCHAR(20));
                                INSERT INTO xsjs_dbseed (version) VALUES('none')");
        $stmt->execute();
    }

    //查当前version
    $stmt=$conn->prepare("SELECT version FROM xsjs_dbseed");
    $stmt->execute();

    $rows=$stmt->fetchAll();
    $v_now=$rows[0][0];
    if($v_now!=$v_new){
        $stmt=$conn->prepare("UPDATE xsjs_dbseed SET version=:v;
            DROP TABLE member;
            DROP TABLE department;
            DROP TABLE qdb_choice;
            DROP TABLE qdb_judge;
            DROP TABLE admin_log;");
        $stmt->bindValue(':v',$v_new);
        $stmt->execute();
        echo '【数据库已重置!!!】';
    }

    //====================================================================
    //查admin_log表,没有则创建
    $stmt=$conn->prepare("SELECT table_name FROM information_schema.tables
                                            WHERE table_name='admin_log';");
    $stmt->execute();
    $resCnt=$stmt->rowCount();

    if($resCnt==0){
        //未创建admin_log表
        $stmt=$conn->prepare("CREATE TABLE admin_log  (
                                                    admin VARCHAR(10) NOT NULL,
                                                    studentcard VARCHAR(10) NOT NULL,
                                                    studentnum VARCHAR(10) NOT NULL,
                                                    operatetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $stmt->execute();
    }

    //===================================================
    //查member表,没有则创建    
    $stmt=$conn->prepare("SELECT table_name FROM information_schema.tables
                                            WHERE table_name='member'");
    $stmt->execute();
    $resCnt=$stmt->rowCount();

    if($resCnt==0){
        //未创建member表
        $stmt=$conn->prepare("CREATE TABLE member (
                                                    username VARCHAR(10) NOT NULL,
                                                    password TEXT NOT NULL,
                                                    score INT NOT NULL DEFAULT -1,
                                                    last_req_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                                    department_id VARCHAR(3) NOT NULL,
                                                    PRIMARY KEY(username)
                                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $stmt->execute();
        $stmt=$conn->prepare("INSERT INTO member (username, password, department_id,score) VALUES (:u,:p,:d,:s);");
        $username='';
        $password='';
        $department='';
        $dbscore=-1;
        $stmt->bindParam(':u', $username);
        $stmt->bindParam(':p', $password);
        $stmt->bindParam(':d', $department);
        $stmt->bindParam(':s', $dbscore,PDO::PARAM_INT);
        $testusers=array(
            array('213170000','123456','09',-1),
            array('213170001','123456','08',-1),
            array('213170002','123456','08',-1),
            array('213170003','123456','08',-1),
            array('213170004','123456','07',-1),
            array('213170005','123456','09',-1),
            array('213170006','123456','09',-1),
            array('213170007','123456','07',-1),
            array('213170008','123456','07',-1),
            array('213170009','123456','07',-1),
            array('213170010','123456','07',-1),
            array('213170011','123456','05',-1),
            array('213170012','123456','09',-1),
            array('213170013','123456','06',-1),
            array('213170014','123456','05',-1),
            array('213170015','123456','05',-1),
            array('213170016','123456','05',-1),
            array('admin09','123456','09',-1),
            array('admin08','123456','08',-1),
            array('admin07','123456','07',-1),
            array('admin06','123456','06',-1),
            array('admin05','123456','05',-1)
        );

        for($depa=1;$depa<=20;$depa++){
            for($i=18;$i<150;$i++){
                $score=mt_rand(0,100);
                if($score>50){
                    $score=mt_rand(0,100);
                }else{
                    $score=-1;
                }

                $cardId='21317'.(strlen($depa)==1?'0':'').$depa.(strlen($i)==1?'0':'').$i;
                $stuId=(strlen($depa)==1?'0':'').$depa.'017'.(strlen($i)==2?'0':'').$i;
                array_push($testusers, array($cardId,$stuId,(strlen($depa)==1?'0':'').$depa,$score));
            }
        }
        
        
        $mem_arr=$testusers;
        for($x=0;$x<count($mem_arr);$x++){
            $username=$mem_arr[$x][0];
            $password=password_encrypt($mem_arr[$x][1]);
            $department=$mem_arr[$x][2];
            $dbscore=$mem_arr[$x][3];
            $stmt->execute();
        }
    }

    //===================================================
    //查department表,没有则创建
    
    $stmt=$conn->prepare("SELECT table_name FROM information_schema.tables
                                            WHERE table_name='department'");
    $stmt->execute();
    $resCnt=$stmt->rowCount();
    
    if($resCnt==0){
        //未创建department表
        $stmt=$conn->prepare("CREATE TABLE department (
            id VARCHAR(3) NOT NULL,
            update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            average DOUBLE(5,2) DEFAULT 0,
            pass_rate DOUBLE(5,2) DEFAULT 0,
            rate DOUBLE(5,2) DEFAULT 0,
            PRIMARY KEY(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $stmt->execute();

        //根据member表初始化department表
        $stmt=$conn->prepare("SELECT DISTINCT department_id FROM member;");
        $stmt->execute();
        $rows=$stmt->fetchAll();
        $stmt=$conn->prepare("INSERT INTO department (id) VALUES(:id);");
        foreach ($rows as $key => $value) {
            $stmt->bindValue(':id',$value['department_id']);
            $stmt->execute();
        }
    }
    
    //=================================================
    //查qdb_choice表,没有则创建
    $stmt=$conn->prepare("SELECT table_name FROM information_schema.tables
                                            WHERE table_name='qdb_choice'");
    $stmt->execute();
    $resCnt=$stmt->rowCount();
    if($resCnt==0){
        $stmt=$conn->prepare("CREATE TABLE qdb_choice(
	                                               body VARCHAR(200) NOT NULL,
	                                               choice_A VARCHAR(50) NOT NULL,
	                                               choice_B VARCHAR(50) NOT NULL,
	                                               choice_C VARCHAR(50) NOT NULL,
	                                               choice_D VARCHAR(50) NOT NULL,
	                                               answer VARCHAR(1) NOT NULL
                                                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $stmt->execute();
        
        $stmt=$conn->prepare("INSERT INTO qdb_choice (body, choice_A, choice_B,choice_C,choice_D,answer)
                                                    VALUES (:body,:ca,:cb,:cc,:cd,:ans);");
        $body='';
        $cha='';
        $chb='';
        $chc='';
        $chd='';
        $ans='';
        $stmt->bindParam(':body', $body);
        $stmt->bindParam(':ca', $cha);
        $stmt->bindParam(':cb', $chb);
        $stmt->bindParam(':cc', $chc);
        $stmt->bindParam(':cd', $chd);
        $stmt->bindParam(':ans', $ans);
        
        $testqch=array(
            //9
            array('我们的组名叫什么?','祖名还没想好','组长没想好','组名还没想好','没想好','C'),
            array('测试题目1这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','测试选项1','测试选项2','测试选项3','测试选项4','A'),
            array('测试题目2这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','测试选项1','测试选项2','测试选项3','测试选项4','B'),
            array('测试题目3这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','测试选项1','测试选项2','测试选项3','测试选项4','C'),
            array('测试题目4这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','测试选项1','测试选项2','测试选项3','测试选项4','D'),
            array('测试题目5这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','测试选项1','测试选项2','测试选项3','测试选项4','A'),
            array('测试题目6这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','测试选项1','测试选项2','测试选项3','测试选项4','B'),
            array('测试题目7这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','测试选项1','测试选项2','测试选项3','测试选项4','C'),
            array('测试题目8这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','测试选项1','测试选项2','测试选项3','测试选项4','D')
  
        );
        
        $qch_arr=$testqch;
        for($x=0;$x<count($qch_arr);$x++){
            $body=$qch_arr[$x][0];
            $cha=$qch_arr[$x][1];
            $chb=$qch_arr[$x][2];
            $chc=$qch_arr[$x][3];
            $chd=$qch_arr[$x][4];
            $ans=$qch_arr[$x][5];
            $stmt->execute();
        }
    }
    
    //=================================================
    //查qdb_judge表,没有则创建
    $stmt=$conn->prepare("SELECT table_name FROM information_schema.tables
                                            WHERE table_name='qdb_judge';");
    $stmt->execute();
    $resCnt=$stmt->rowCount();
    if($resCnt==0){
        $stmt=$conn->prepare("CREATE TABLE qdb_judge(
	                                               body VARCHAR(200) NOT NULL,
	                                               answer VARCHAR(1) NOT NULL
                                                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $stmt->execute();
        
        $stmt=$conn->prepare("INSERT INTO qdb_judge (body,answer)
                                                    VALUES (:body,:ans);");
        $body='';
        $ans='';
        $stmt->bindParam(':body', $body);
        $stmt->bindParam(':ans', $ans);
        
        $testqjd=array(
            //9
            array('我们的组名还没想好','F'),
            array('测试判断题1这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','F'),
            array('测试判断题2这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','T'),
            array('测试判断题3这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','F'),
            array('测试判断题4这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','T'),
            array('测试判断题5这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','F'),
            array('测试判断题6这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','T'),
            array('测试判断题7这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','F'),
            array('测试判断题8这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述这是题目描述','T')

        );
        
        $qjd_arr=$testqjd;
        
        for($x=0;$x<count($qjd_arr);$x++){
            $body=$qjd_arr[$x][0];
            $ans=$qjd_arr[$x][1];
            $stmt->execute();
        }
    }

    //=============================================
    //新设置的appeals表
    $stmt=$conn->prepare("SELECT table_name FROM information_schema.tables WHERE table_name='appeals'");
    $stmt->execute();
    $resCnt = $stmt->rowCount();

    if($resCnt==0){                                              //测试用的语句, 没有表格则创建表格
        $stmt = $conn->prepare("CREATE TABLE appeals (
                id VARCHAR(15) NOT NULL,
                name TEXT NOT NULL,
                allowed INT DEFAULT 1,
                content TEXT DEFAULT NULL,
                update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO appeals(id, name, allowed) VALUES (0, 'std', 0)");
        $stmt->execute();
    }
    //==============================================================================

    
    $GLOBALS['resFlag']='ok';
}
catch(PDOException $ex){
    $GLOBALS['resFlag']='fail';
    $GLOBALS['errorMsg']=$ex->getMessage();
}

$conn=null;
?>