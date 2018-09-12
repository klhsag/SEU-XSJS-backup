$(document).ready(function(){
	//页面加载完执行
	//showRegisterAns();
});


function onRegisterSubmit(){
	var username=document.getElementById('reg_username').value;
	var password=document.getElementById('reg_pwd').value;

	var xhr=new XMLHttpRequest();
	xhr.open("post","register_handler.php",true);

	xhr.onreadystatechange=function(){
		if(xhr.readyState === 4 && xhr.status === 200) {
			var resObj=JSON.parse(xhr.responseText);
				if(resObj['flag']=='fail'){
					if(isIE()){
						alert(resObj['msg']);
					}else{
						materialAlert('提示',resObj['msg'],function(result){
						});
					}
				}else if(resObj['flag']=='ok'){
					if(resObj['found']=='no'){
						if(isIE()){
							if(confirm("数据库查无用户"+resObj['username']+",是否要新建这个账号,并设其密码为"+resObj['password']+"?")){
								yesSubmit(username,password);
							}
						}else{
							materialConfirm('提示',"数据库查无用户"+resObj['username']+",是否要新建这个账号,并设其密码为"+resObj['password']+"?",function(result){
								if(result==true){
									yesSubmit(username,password);
								}
							});
						}
					}else{
						if(isIE()){
							if(confirm("数据库中显示用户"+resObj['username']+"的密码是"+resObj['oldpassword']+",院系编号为"+resObj['did']+",是否要将密码重设为"+resObj['password']+"并修改该学生的院系编号?(警告:请务必确保你录入的信息是正确的,否则会影响其他考生的信息,后台会记录管理员的所有注册行为.)")){
								yesSubmit(username,password);
							}
						}else{
							materialConfirm('提示',"数据库中显示用户"+resObj['username']+"的密码是"+resObj['oldpassword']+",院系编号为"+resObj['did']+",是否要将密码重设为"+resObj['password']+"并修改该学生的院系编号?(警告:请务必确保你录入的信息是正确的,否则会影响其他考生的信息,后台会记录管理员的所有注册行为.)",function(result){
								if(result==true){
									yesSubmit(username,password);
								}
							});
						}
					}
				}
		}
	};
	xhr.setRequestHeader('CSRF-TOKEN',csrftoken);
	xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	xhr.send("username="+username+"&password="+password+"&force=0");

}

function yesSubmit(username,password){
	var xhr=new XMLHttpRequest();
	xhr.open("post","register_handler.php",true);

	xhr.onreadystatechange=function(){
		if(xhr.readyState === 4 && xhr.status === 200) {
			var resObj=JSON.parse(xhr.responseText);
			if(isIE()){
				alert(resObj['msg']);
			}else{
				materialAlert('提示',resObj['msg'],function(result){
				});
			}
		}
	};
	xhr.setRequestHeader('CSRF-TOKEN',csrftoken);
	xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	xhr.send("username="+username+"&password="+password+"&force=1");

}


function isIE()

{

	if(!!window.ActiveXObject || "ActiveXObject" in window)

		return true;

	else

		return false;

}