<?php
$conf_choice_all=9; //选择题在数据库中的总数,需要和数据库一致
$conf_judge_all=9;  //判断题在数据库中的总数,需要和数据库一致
$conf_choice_cnt=5;
$conf_judge_cnt=5;
$conf_choice_score=10;
$conf_judge_score=10;
$second_required_between_requests=10;  //刷卷最低间隔
$second_required_before_submit=30;
$conf_time_limit=60*30+60;             //60s是考虑网络延迟等原因的固定加时
$debug_mode=true;   //开启debug_mode后可以反复做题,否则服务器会拒绝考试请求。 //此功能已废弃
?>
