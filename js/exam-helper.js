$(document).ready(function(){
//这块代码在页面加载完毕执行
setLayout('before_exam');
});




//以下代码在页面加载前执行
var pageArgs=JSON.parse(pageArgsStr);

if(!pageArgs['username']){
	window.location.assign('index.php');
}

var exam_data;//题目内容,由服务器来赋值

var qnum_current=1;//当前题号
var ans_current='';//当前题目选择的答案
var ans_arr=[];//答案数组(记录用户选择的答案,下标是题号,从1开始)
var ans_string='';//答案字符串,比如'ABTF',最后发送给服务器用来判分

var choice_cnt;//选择题总数
var judge_cnt;//判断题总数

//系统相关
var exam_click_registered=false;//点击事件已注册(初始为假,注册后为真)

//用户信息相关
var user_name=pageArgs['username'];//用户名

var exam_time_limit=pageArgs['time_limit'];//考试时限,单位秒
var exam_start_time;
var exam_timer_id;

function examTimerInit(){
	var now=(new Date()).getTime()/1000;
	exam_start_time=now;
	exam_timer_id=setInterval("examTimer()",1000);
	showDiv('exam_TIME');
	hideDiv('exam_TIME_OUT');
}

function examTimer(){
	var time=(new Date()).getTime()/1000;
	var past=time-exam_start_time;
	var left=Math.round(exam_time_limit-past);
	if(left<0){
		showDiv('exam_TIME_OUT');
		hideDiv('exam_TIME');
		submitPaper(true);//参数为true表示考试时间已到强制提交
		clearInterval(exam_timer_id);
		return;
	}
	//var dom=document.getElementById('exam_SECOND');
	var minute = parseInt(left/60);
	var second = left%60;
	//dom.innerHTML = minute+"分"+second;
	document.getElementById("exam_TIME").innerHTML=minute+":"+second;
	//document.getElementById('exam_TIME').innerHTML = 
	//这将是一个按一定频率反复被调用的函数
}

var shutdown_time_limit=60;
var shutdown_start_time;
function shutdownTimerInit(){
	shutdown_start_time=(new Date()).getTime()/1000;
	var xhr=new XMLHttpRequest();
	xhr.open("get",'login_handler.php');//申请注销
	xhr.send(null);
	setInterval(shutdownTimer,1000);
}

function shutdownTimer(){
	var dom=document.getElementById('exam_finish_TIME');
	var time=(new Date()).getTime()/1000;
	var past=time-shutdown_start_time;
	var left=Math.round(shutdown_time_limit-past);
	if(left<0){
		window.location.assign('index.php');
		return;
	} 
	dom.innerHTML=left;
}

//获取指定题目
function getQ(qnum, which){
	var data;
	var index;
	if(qnum<=choice_cnt){
		data = exam_data.choice;
		index = qnum-1;
	}else{
		data = exam_data.judge;
		index = qnum-choice_cnt-1;
	}

	which = which.toLowerCase();
	switch(which){
		case 'body':
		return data[index].question;
		break;
		case 'a':
		return data[index].opt_a;
		break;
		case 'b':
		return data[index].opt_b;
		break;
		case 'c':
		return data[index].opt_c;
		break;
		case 'd':
		return data[index].opt_d;
		case 't':
		return '正确';
		break;
		case 'f':
		return '错误';
		break;
		case 'n':
		return '未填写';
		break;
	}
}

//跳转到指定题号的题目,qnum是题号,从1开始
function gotoQ(qnum){
	var examDiv = document.getElementById('exam');
	if (examDiv.style.display!=='') examDiv.style.display='';
	var part1 = document.getElementById('q_choice');
	var part2 = document.getElementById('q_judge');
	var lastBtn = document.getElementById('q_last');
	var nextBtn = document.getElementById('q_next');
		
	if(isNaN(qnum)) qnum=1;	
	lastBtn.style.display = (qnum==1)?'none':'inline';	//如果是第一题
	if(qnum==(choice_cnt+judge_cnt)){                 //如果是最后一题
		document.getElementById('q_submit').style.display='inline';
		document.getElementById('q_next').style.display='none';
	}else{
		document.getElementById('q_submit').style.display='none';
		document.getElementById('q_next').style.display='inline';
	}
	
	if(qnum<1 || qnum>(choice_cnt+judge_cnt)){
		return;//滑稽.jpg
	}
	
	ans_arr[qnum_current] = ans_current;//保存当前题目的答案
	qnum_current = qnum;                //跳转题目
	ans_current = ans_arr[qnum_current];//读取当前题目的答案
	
	

	var questionDiv=document.getElementById("q_body");
	var optionsDiv;
	
	if(qnum<=choice_cnt){
		//选择题
		document.getElementById('q_type').innerHTML = '单项选择';
		document.getElementById('q_cnt').innerHTML = choice_cnt;
		document.getElementById('q_ccnt').innerHTML = qnum;
		part1.style.display='';
		part2.style.display='none';
		optionsDiv=document.getElementById('q_choice').children[0];
		questionDiv.innerHTML=getQ(qnum,'body');
		optionsDiv.children[0].innerHTML=getQ(qnum,'A');
		optionsDiv.children[1].innerHTML=getQ(qnum,'B');
		optionsDiv.children[2].innerHTML=getQ(qnum,'C');
		optionsDiv.children[3].innerHTML=getQ(qnum,'D');
	}else{
		//判断题
		document.getElementById('q_type').innerHTML = '判断题';
		document.getElementById('q_cnt').innerHTML = judge_cnt;
		document.getElementById('q_ccnt').innerHTML = qnum-choice_cnt;
		part1.style.display='none';
		part2.style.display='';
		optionsDiv=document.getElementById('q_judge').children[0];
		questionDiv.innerHTML=getQ(qnum,'body');
		optionsDiv.children[0].innerHTML=getQ(qnum,'T');
		optionsDiv.children[1].innerHTML=getQ(qnum,'F');
	}

	selectOpt(ans_current);
}

function selectOpt(which){
	var i=10;

	if(which=='A') i=0;
	if(which=='B') i=1;
	if(which=='C') i=2;
	if(which=='D') i=3;

	if(which=='T') i=0;
	if(which=='F') i=1;
	
	var div;
	if(qnum_current<=choice_cnt){
		div=document.getElementById("q_choice").children[0];
	}else{
		div=document.getElementById("q_judge").children[0];
	}
	
	for(var k=0;k<4;k++){
		if (!div.children[k]) continue;
		if (k==i){
			div.children[k].setAttribute('class','qbutton_selected'); 
			//alert(div.children[k]);
		}else{
			div.children[k].setAttribute('class','qbutton'); 
		}
	}

}

//向服务器发送开始考试的请求
function onExamRequest(){
	setLayout('exam_loading');
	var xmlhttp=new XMLHttpRequest();
	xmlhttp.open("get","exam_content.php",true);
	
	xmlhttp.onreadystatechange=function(){
		if(xmlhttp.readyState === 4 && xmlhttp.status === 200) {
			var resObj=JSON.parse(xmlhttp.responseText);
			/*
			responseText是服务器返回的结果,它是一个json字符串,解析后内容如下:
				//flag,值为'ok'或'fail'
				//msg,当请求顺利完成时(ok),值为一个json字符串,表示考试题目
				//当请求失败时(fail).值为失败信息.
			*/
			if(resObj.flag=='fail'){
				if(isIE()){
					alert(resObj['msg']);
					setLayout('before_exam');
				}else{
					materialAlert('提示',resObj['msg'],function(result){
						setLayout('before_exam');
					});
				}
				
				return;
			}
			
			examTimerInit();                         //初始化考试时间

			setLayout('exam');
			exam_data=JSON.parse(resObj.msg);
			
			choice_cnt=exam_data.choice.length;
			judge_cnt=exam_data.judge.length;
			
			//初始化
			ans_arr=[];
			ans_current='';
			qnum_current=1;
			

			gotoQ(1);

			initProgressBar();
			
			//注册点击事件(选项\上一题\下一题)
			if(!exam_click_registered){
				exam_click_registered=true;//防止重复注册事件
				$("#exam").click(function(e){
					var value=e.target.getAttribute("value");
					if(value===null){
						value=$.trim($(e.target).parents("button").text());
					}
					if(value=='A' || value=='B' || 
					   value=='C' || value=='D' ||
					   value=='T' || value=='F')
					{

						selectOpt(value);
						ans_current=value;
						ans_arr[qnum_current] = ans_current;//保存当前题目的答案
						freshBar(1,30);                      //更新进度条
					}else if(value=='下一题'){
						gotoQ(qnum_current+1);
					}else if(value=='上一题'){
						gotoQ(qnum_current-1);
					}else if(value=='提交'){
						ans_arr[qnum_current]=ans_current;
						submitPaper();
					}
				});
			}
		}
	};
	xmlhttp.setRequestHeader('CSRF-TOKEN',pageArgs['csrftoken']);
	xmlhttp.send(null);
}

//提交试卷
function submitPaper(timeout){

	var timeout = timeout || false;//ie不支持函数参数缺省值,这行语句将timeout缺省为false
	//alert("s");
	ans_string='';
	for(var i=1;i<(choice_cnt+judge_cnt+1);i++){
		if(ans_arr[i]){
			ans_string+=ans_arr[i];
		}else{
			if(timeout){
				ans_arr[i]='N';
				ans_string+='N';
			}else{
				if(isIE()){
					alert("有题目未完成,不能提交!");
				}else{
					materialAlert('提示','有题目未完成,不能提交!',function(result){
					});
				}
				return;
			}
		}
	}
	//alert("你的答案:"+ans_string);
	
	if(!timeout){
		if(isIE()){
			if(confirm("你确定要提交试卷吗?")){
				yesSubmit();
			}
		}else{
			materialConfirm('提示','你确定要提交试卷吗?',function(result){
				if(result==true)
					yesSubmit();
			});
		}
		
	}else{
		yesSubmit();
	}
	
}

function yesSubmit(){
	clearInterval(exam_timer_id);
	var xmlhttp=new XMLHttpRequest();
	xmlhttp.open("get","submit_paper.php?ans="+ans_string,true);
	
	xmlhttp.onreadystatechange=function(){
		if(xmlhttp.readyState === 4 && xmlhttp.status === 200) {
			var resObj=JSON.parse(xmlhttp.responseText);
			
			/*
			此处协议:
				resObj.flag: 标记字符串 值为ok/fail
				resObj.score: 整型 得分
				resObj.wrongs: json对象 一个二维数组 第一维是错题 第二维是题号\正确答案
				resObj.msg: 错误提示
			*/
			
			switch(resObj.flag){
			case 'ok':
				clearInterval(exam_timer_id);
				if(isIE()){
					alert('试卷提交成功,卷已判!');
					setLayout('exam_finish',resObj['wrongs'],resObj['score']);
				}else{
					materialAlert('提示','试卷提交成功,卷已判!',function(result){
						setLayout('exam_finish',resObj['wrongs'],resObj['score']);
					});
				}
				break;
			default:
				if(isIE()){
					alert(resObj.msg);
				}else{
					materialAlert('提示',resObj.msg,function(result){
					});
				}
			}
			
		}
	};
	xmlhttp.setRequestHeader('CSRF-TOKEN',pageArgs['csrftoken']);
	xmlhttp.send(null);
}


//-------------------------布局相关----------------------------
var layout_arr=new Array();
layout_arr[0]='before_exam';
layout_arr[1]='exam_loading';
layout_arr[2]='exam';
layout_arr[3]='exam_finish';
//所有index.html里参与页面布局的div都要在这个数组里注册

function hideDiv(id){
	document.getElementById(id).style.display = "none";
}

function showDiv(id){
	document.getElementById(id).style.display = "";
}

function showOnly(arr){
	for(var i=0;i<4;i++){
		hideDiv(layout_arr[i]);
	}
	for(i=0;i<arr.length;i++){
		showDiv(arr[i]);
	}
}

function setLayout(layout){
	switch(layout){
	case 'before_exam':
		showOnly(['before_exam']);
		document.getElementById('before_exam_NAME').innerHTML=user_name;
		break;
	case 'exam_loading':
		showOnly(['exam_loading']);
		break;
	case 'exam':
		showOnly(['exam']);
		break;
	case 'exam_finish':
		examBar.destroy();
		//刚考完试
		showOnly(['exam_finish']);
		var wrongs=arguments[1];//resObj['wrongs']
		var score=arguments[2];//resObj['score']
		var table='<table border="1px" style="margin: 0 auto; border-collapse:collapse; font-size:18px; text-align:center;"><th>错题</th><th>你的答案</th><th>正确答案</th></tr></thead><tbody>';

		if(!wrongs) wrongs=[];
		for(var i=0;i<wrongs.length;i++){
			var q=wrongs[i][0];
			var right=wrongs[i][1];

			table+='<tr>';
			table+=('<td>'+getQ(q,'body')+'</td>');
			table+=('<td>'+getQ(q,ans_arr[q])+'</td>');
			table+=('<td>'+getQ(q,right)+'</td>');
			table+='</tr>';

		}
		table+='</tbody></table>';

		if(score==100){
			table='恭喜你!'
		}
		document.getElementById('exam_finish_NAME').innerHTML=user_name;
		document.getElementById('exam_finish_SCORE').innerHTML=score;
		document.getElementById('exam_finish_WRONGS').innerHTML=table;
		shutdownTimerInit();
		break;
	}
}

//==========================================================================
//答题进度条

var examBar;

function initProgressBar(){
	initBarData();
	configAndDrawChartIn('exam_hc');
	freshBar(1,30);
}

var barData=[];//进度条数据,题目下标从1开始
function initBarData(){
	//根据exam_data初始化barData,这个初始化只用进行一次
	for(var i=0;i<30;i++){
		var q;
		var t="choice";
		if(i+1>choice_cnt){
			t="judge";
			if(exam_data.judge[i-choice_cnt]){
				q=exam_data.judge[i-choice_cnt].question;
			}else{
				q="题目空缺";
			}
			
		}else{
			q=exam_data.choice[i].question;
		}
		q=q.substr(0,10)+"...";
		barData[i]={
			x: 0,
			y: i,
			qbody: q,
			qnum: i+1,
			done: "n",
			type: t
		};
	}
}
//下面这两行,是为了用英文名代替颜色
var colors =  {
	"done": "#46488b",
	"notyet": "#000000",
	"white": "#FFFFFF",
	"whitesmoke": "#F5F5F5",
};
Highcharts.Color.prototype.names = colors;
//更新进度条,显示题号从start到end的所有题目,并且依据题目的状态设置点的样式
var fcnt=1;
function freshBar(start,end){
	/*
	barData=barData.map(function(item){
		item.x=fcnt;
		return item;
	});*/

	//fcnt++;
	if(!barData[start]){
		return;
	}
	for(var i=start;i<=end;i++){
		if(!ans_arr[i]){
			barData[i-1].color="#000000";
		}else{
			barData[i-1].color="#009688";
		}
	}
	//alert(barData);
	examBar.series[0].setData(barData,false,false,true);
	examBar.series[0].show();
}

//创建进度条并配置 
function configAndDrawChartIn(containerId){
	if(!document.getElementById(containerId)){
		alert("考试进度条错误:找不到id为"+containerId+"的元素!");
	}

	examBar=Highcharts.chart('exam_hc', {
		title:{
			text:null
		},
		chart: {
			type: 'heatmap',
			inverted: true,
			animation: false,
			backgroundColor: 'rgba(0,0,0,0)',
			//marginLeft: 0,
			//marginRight: 0,
			//marginTop:0,
			//marginBottom: 70,
			margin: [0,0,70,0],
			spacing: [0,0,0,0]

		},
		navigation:{
			buttonOptions:{
				enabled: false
			}},
			legend: {
				enabled: false
			},
			xAxis: {
				title: {
					text: null
				},
				labels: {
					enabled: false
				}
			},
			yAxis: {
				title: {
					text: null
				},
				labels: {
					enabled: false
				}
			},
			credits: {
				enabled: false
			},
			tooltip: {
				useHTML: true,
				headerFormat: '',
				pointFormatter: function(){
					var msg='<div style="font-size:1.3em">第'+this.qnum+'题<br>';
					if(this.type=='choice'){
						msg+='(单选)';
					}else{
						msg+='(判断)';
					}
					msg+= this.qbody;
					msg+='</div>'
					return msg;
				}
			},
			series: [{
				borderWidth: 0,
				borderColor: "white",
				data: [{x:0,y:1,value:0},{x:0,y:2,value:0},{x:0,y:3,value:0},{x:0,y:4,value:0},{x:0,y:5,value:0}],
				events: { 
					click: function(e) { 
						gotoQ(e.point.qnum);
					} 
				}
			}]
		});
}
//---------------------------------------------------
function isIE()

{

	if(!!window.ActiveXObject || "ActiveXObject" in window)

		return true;

	else

		return false;

}