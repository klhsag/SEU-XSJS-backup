$(document).ready(function(){
	//页面加载完执行
	switch(pageArgs['flag']){
		case 'not_login':
			setLayout('login');
			onRequestCaptch();
			break;
		case 'already_login':
			user_name=pageArgs['username'];

			if(user_name[0]>'9'){//只有管理员账户以admin开头而不是纯数字. 修正管理员回退时发现此页面的bug
				setLayout('admin_attention');
			}else{
				if(pageArgs['score']&&pageArgs['score']>=0){
					user_score=pageArgs['score'];
					setLayout('check_score');
				}
				else
					setLayout('exam_attention');
			}
			break;
	}
});

//页面加载前执行
var pageArgs=JSON.parse(pageArgsStr);

var user_name;
var user_score;

function onLogoutSubmit(){
	var xmlhttp=new XMLHttpRequest();
	//约定:对于login_handler.php, get是退出, post是登录
	xmlhttp.open("get","login_handler.php",true);
	
	xmlhttp.onreadystatechange=function(){
		if(xmlhttp.readyState === 4 && xmlhttp.status === 200) {
			var resObj = JSON.parse(xmlhttp.responseText);
			if(isIE()){
				alert(resObj['msg']);
				if(resObj['flag']=='ok'){
					window.location.assign('index.php');
				}
			}else{
				materialAlert('提示',resObj['msg'],function(result){
					if(resObj['flag']=='ok'){
						window.location.assign('index.php');
					}
				});
			}
		}
	};
	xmlhttp.send(null);
}

function onLoginSubmit(){
	var username=document.getElementById("login_username").value;
	var password=document.getElementById("login_password").value;
	var captch=document.getElementById('login_captch').value;

	var xmlhttp=new XMLHttpRequest();
	xmlhttp.open("post","login_handler.php",true);

	xmlhttp.onreadystatechange=function(){
		if(xmlhttp.readyState === 4 && xmlhttp.status === 200) {
			var resObj=JSON.parse(xmlhttp.responseText);
			if(resObj['flag']=='ok'){
				user_name=resObj['username'];
				switch(resObj['type']){
					case 'admin':
					if(isIE()){
						alert("管理员身份验证成功!");
						window.location.assign('department.php');
					}else{
						materialAlert('提示','管理员身份验证成功!',function(result){
							window.location.assign('department.php');
						});
					}
					break;

					case 'user_not_done':
					if(isIE()){
						alert("登录成功,将转入考试页面!");
						window.location.assign('exam.php');
					}else{
						materialAlert('提示','登录成功,将转入考试页面!',function(result){
							window.location.assign('exam.php');
						});
					}
					break;

					case 'user_done':
					user_score=resObj['score'];
					setLayout('check_score');
					break;
				}

			}else{
				if(isIE()){
					alert(resObj['msg']);
					onRequestCaptch();
				}else{
					materialAlert('提示',resObj['msg'],function(result){
						onRequestCaptch();
					});
				}
			//document.getElementById('login').children[2].disabled=false;
			}
		}
	};

	xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	var data="username="+username+"&password="+password+"&captch="+captch;
	xmlhttp.send(data);
}
//---------------------------------验证码-------------------------------------
function onRequestCaptch(){
	//alert("captch");
	var xmlhttp=new XMLHttpRequest();
	//约定:对于login_handler.php, get是退出, post是登录
	xmlhttp.open("get","captch_handler.php",true);	
	xmlhttp.onreadystatechange=function(){
		if(xmlhttp.readyState === 4 && xmlhttp.status === 200) {
			document.getElementById("captch_img").src=xmlhttp.responseText;
		}
	};
	xmlhttp.send(null);
}
//------------------------------背景图片-------------------------------------
var bg_timer_id;
var bg_cur=0;
var bg_ready=0;
var bg_all=6;
var bg_last=0;
var que=[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15];

function initBgTimer(){
	if(isIE())return;
	if(bg_timer_id)clearInterval(bg_timer_id);
	bg_timer_id=setInterval("bgTimer()",5000);
	bg_ready=1;
	bg_cur=1;

	for(var i=0;i<bg_all;i++){
		var r=Math.floor(Math.random()*15);
		var tmp=que[i];
		que[i]=que[r];
		que[r]=tmp;
	}

	getBackgroundImage(que[0]);
}

function bgTimer(){
	if(bg_ready>1){
		bg_cur++;
		bg_cur%=bg_ready;
		var now=document.getElementById('bg_img').children[bg_cur];
		if(now){
			now.style.opacity=1;
			if(bg_last){
				document.getElementById('bg_img').children[bg_last-1].style.opacity=0;
			}
			bg_last=bg_cur+1;
		}
	} 
	else bg_cur=1;
}


function getBackgroundImage(bgNumber){
	var xhr = new XMLHttpRequest();
	xhr.open("GET","img/bg"+bgNumber+".jpg",true);
	xhr.responseType="blob";
	xhr.onload=function(){
		if(this.readyState!=4)return;

		var reader = new FileReader();

		reader.onload=function(){
			var img=document.createElement('img');
			img.src=this.result;
			img.style="position:fixed;top:0px;width:100%;height:100%;opacity:0;transition:2s;";
			document.getElementById('bg_img').appendChild(img);
			if(bg_ready<bg_all)
			getBackgroundImage(que[bg_ready++]);
		};
		reader.readAsDataURL(this.response);
	}
	xhr.send(null);
}
//----------------------------------布局相关----------------------------------------
var layout_arr=['login', 'check_score', 'exam_attention', 'admin_attention'];

function hideDiv(id){
	document.getElementById(id).style.display = "none";
}

function showDiv(id){
	document.getElementById(id).style.display = "";
}

function showOnly(arr){
	for(var i=0;i<layout_arr.length;i++){
		hideDiv(layout_arr[i]);
	}
	for(i=0;i<arr.length;i++){
		showDiv(arr[i]);
	}
}

function setLayout(layout){
	switch(layout){
	case 'login':
		showOnly(['login']);
		initBgTimer();
		//document.getElementById('login_submit').disabled=false;
		break;
	case 'check_score':
		//考完的再登录查分
		showOnly(['check_score']);
		document.getElementById('check_score_NAME').innerHTML=user_name;
		document.getElementById('check_score_SCORE').innerHTML=user_score;
		break;
	case 'exam_attention':
		showOnly(['exam_attention']);
		document.getElementById('exam_attention_NAME').innerHTML=user_name;
		break;
	case 'admin_attention':
		showOnly(['admin_attention']);
		document.getElementById('admin_attention_NAME').innerHTML=user_name;
		break;
	}

}
//---------------------------------------------------
function isIE()

{

	if(!!window.ActiveXObject || "ActiveXObject" in window)

		return true;

	else

		return false;

}