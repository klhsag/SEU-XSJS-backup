$(document).ready(function(){
	//页面加载完执行
	document.getElementById("stu_content").innerHTML = "";
	document.getElementById("stu_content").disabled = true;
	//document.getElementById("appeal_fresh").style.display = "" ;
	//document.getElementById("appeal_submit").style.display = "none" ;
});
 
//页面加载前执行

var name_patt = /^[0-9]{2,2}.{1,1}[0-9]{5,}[^0-9]+$/;
var content_patt = /.{15}/;

function onFreshSubmit(){
	var stu_name=document.getElementById("stu_name").value;
	var content=document.getElementById("stu_content").value;

	if (!name_patt.test(stu_name)){
		alert('请正确填写院系和姓名！');
		return;
	}

	//刷新时不需要验证15字要求

	var xmlhttp=new XMLHttpRequest();

	xmlhttp.open("post", "appeal_handler.php", true);

	xmlhttp.onreadystatechange=function(){
		if(xmlhttp.readyState === 4 && xmlhttp.status === 200) {
			var appRes = JSON.parse(xmlhttp.responseText);
			//alert(appRes['type']);
			//alert(appRes['msg']);
			document.getElementById("stu_content").value = appRes['content'];
			if (appRes['allow']==='true'){
				document.getElementById("stu_content").disabled = false;
			}else{
				document.getElementById("stu_content").disabled = true;
			}
		}
	};

	xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	var data=encodeURI("stu_name="+stu_name+"&content="+content+"&type=fresh");   //type=fresh指出指令为更新
	xmlhttp.send(data);

}

function onAppealSubmit(){
	var stu_name=document.getElementById("stu_name").value;
	var content=document.getElementById("stu_content").value;

	if (!name_patt.test(stu_name)){
		alert('请正确填写院系和姓名！');
		return;
	}
	if (document.getElementById("stu_content").disabled){
		alert('您现在没有申诉权限！点击刷新按钮以验证您的权限');
		return;
	}
	if (!content_patt.test(content)){                 //要在验证权限之前写
		alert('请详细描述问题内容！不应少于15字');
		return;
	}

	var xmlhttp=new XMLHttpRequest();

	xmlhttp.open("post", "appeal_handler.php", true);

	xmlhttp.onreadystatechange=function(){
		if(xmlhttp.readyState === 4 && xmlhttp.status === 200) {
			var appRes = JSON.parse(xmlhttp.responseText);
			//alert(appRes['type']);
			//alert(appRes['msg']);
			document.getElementById("stu_content").value = appRes['content'];
			if (appRes['allow']==='true'){
				document.getElementById("stu_content").disabled = false;
			}else{
				document.getElementById("stu_content").disabled = true;
			}
		}
	};

	xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	var data=encodeURI("stu_name="+stu_name+"&content="+content+"&type=appeal");   //type=fresh指出指令为更新
	xmlhttp.send(data);

}