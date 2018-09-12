$(document).ready(function(){
	//页面加载完执行

	jQuery.extend(jQuery.fn.dataTableExt.oSort, {
	    "html-score-pre": function (a) {
	        if(isNaN(a))a=-1;
	        else a=parseInt(a);
	        return a;
	    },

	    "html-score-asc": function (a, b) {                //正序排序引用方法
	        return a-b;
	    },

	    "html-score-desc": function (a, b) {                //倒序排序引用方法
	        return b-a;
	    },

	    "html-float-pre": function (a) {
	        return parseFloat(a);
	    }


	});

	onRequestGrades(false);
	document.getElementById("admin_id").innerHTML=php_department_id;


	initDataChart();




});

//页面加载前执行
var grades_data=[];



function dataTable1(tid){
	$('#'+tid).DataTable(
		{
			"aoColumnDefs": [{ "sType": "html-score", "aTargets": [1] }]
		} 
	);
}

function dataTable2(tid){
	$('#'+tid).DataTable(
		{
			"aoColumnDefs": [{ "sType": "html-float", "aTargets": [1,2] }]
		} 
	);
}

function onRequestGrades(real){
	if(real===undefined) real=true;
	var xmlhttp=new XMLHttpRequest();
	xmlhttp.open("get","depa_info_request.php",true);
	
	xmlhttp.onreadystatechange=function(){
		if(xmlhttp.readyState === 4 && xmlhttp.status === 200) {
			var resObj=JSON.parse(xmlhttp.responseText);
			if(resObj.flag=='ok'){
				grades_data=resObj['gdata'];
				stat_data=resObj['stat'];

				var table1 = produceTable('table_glob', stat_data,'院系','平均分','完成人数比例(%)','已完成学生的及格率(%)');
				document.getElementById('stat_table').innerHTML = table1;

				for(var i=0;i<grades_data.length;i++){
					if(grades_data[i][1]=="-1")grades_data[i][1]="未完成"
				}




				var table2 = produceTable('tableId', grades_data,'学号','分数');
				document.getElementById('grade_table').innerHTML = table2;

				var table3 = produceTable('totalGrades', grades_data,'学号','分数');
				document.getElementById('totalGradesDiv').innerHTML = table3;

				dataTable1('tableId');
				dataTable2('table_glob');
				updateDataChart();



				if(real)
					if(isIE()){
						alert("已从服务器获取最新数据!");
					}else{
						materialAlert('提示','已从服务器获取最新数据!',function(result){
						});
					}
				
				//alert(resObj.gdata);
			}else{
				if(isIE()){
						alert(resObj.msg);
				}else{
					materialAlert('提示',resObj.msg,function(result){
					});
				}
				
			}
		}
	};
	xmlhttp.send(null);
}

function produceTable(table_name, data){
	//备注:data必须是个二维数组
	//此函数具体用法参考被调用的地方
	var rowCnt=data.length;
	var colCnt=arguments.length -1 -1;
	var table='<table id="'+table_name+'"class="display" cellspacing="0" width="100%" style="text-align: center;">';
	table+='<thead><tr>';
	for(var i=0;i<colCnt;i++){
		table+='<th>';
		table+=arguments[i +1 +1];
		table+='</th>';
	}
	table+='</tr></thead><tbody>';

	for(i=0;i<rowCnt;i++){
		table+='<tr>';
		for(var j=0;j<colCnt;j++){
			table+='<td>';
			table+=data[i][j];
			table+='</td>';
		}
		table+='</tr>';
	}

	table+='</tbody></table>';
	return table;

}

function onLogoutSubmit(){
	var xmlhttp=new XMLHttpRequest();
	//约定:对于login_handler.php,get是退出,post是登录
	xmlhttp.open("get","login_handler.php",true);
	
	xmlhttp.onreadystatechange=function(){
		if(xmlhttp.readyState === 4 && xmlhttp.status === 200) {
			var resObj=JSON.parse(xmlhttp.responseText);
			if(isIE()){
				alert(resObj.msg);
				if(resObj['flag']=='ok'){
					window.location.assign('index.php');
				}
			}else{
				materialAlert('提示',resObj.msg,function(result){
					if(resObj['flag']=='ok'){
					window.location.assign('index.php');
					}
				});
			}
			
		}
	};
	xmlhttp.send(null);
}


var idTmr;
function  getExplorer() {
	var explorer = window.navigator.userAgent ;
	//ie 
	if (explorer.indexOf("MSIE") >= 0||(!!window.ActiveXObject || "ActiveXObject" in window)) {
	    return 'ie';
	}
	//firefox 
	else if (explorer.indexOf("Firefox") >= 0) {
	    return 'Firefox';
	}
	else if(explorer.indexOf("Edge") >= 0) {
		return 'Edge';
	}
	//Chrome
	else if(explorer.indexOf("Chrome") >= 0){
	    return 'Chrome';
	}
	//Opera
	else if(explorer.indexOf("Opera") >= 0){
	    return 'Opera';
	}
	//Safari
	else if(explorer.indexOf("Safari") >= 0){
	    return 'Safari';
	}

}
function ExportToExcel() {//整个表格拷贝到EXCEL中
	var signal=getExplorer();
	if(signal=='ie')
	{
	    try{       
            var curTbl = document.getElementById('totalGrades'); 
            var oXL = new ActiveXObject("Excel.Application"); 
            //创建AX对象excel  
            var oWB = oXL.Workbooks.Add(); 
            //获取workbook对象  
            var oSheet = oWB.ActiveSheet; 

            var lenRow = curTbl.rows.length; 
            //取得表格行数  
            for (i = 0; i < lenRow; i++) 
            { 
                var lenCol = curTbl.rows(i).cells.length; 
                //取得每行的列数  
                for (j = 0; j < lenCol; j++) 
                { 
                    oSheet.Cells(i + 1, j + 1).value = curTbl.rows(i).cells(j).innerText;  

                } 
            } 
            oXL.Visible = true; 
            //设置excel可见属性  
      }catch(e){ 
            if((!+'/v1')){ //ie浏览器  
            if(isIE()){
            	alert("无法启动Excel，请确保电脑中已经安装了Excel!\n如果已经安装了Excel，"+"请调整IE的安全级别。\n具体操作：\n"+"工具 → Internet选项 → 安全 → 自定义级别 → ActiveX 控件和插件 → 对未标记为可安全执行脚本的ActiveX 控件初始化并执行脚本 → 启用 → 确定");
            }else{
            	materialAlert('提示',"无法启动Excel，请确保电脑中已经安装了Excel!\n如果已经安装了Excel，"+"请调整IE的安全级别。\n具体操作：\n"+"工具 → Internet选项 → 安全 → 自定义级别 → ActiveX 控件和插件 → 对未标记为可安全执行脚本的ActiveX 控件初始化并执行脚本 → 启用 → 确定",function(result){
				});
            }
            
           }else{ 
           	if(isIE()){
           		alert('请使用IE浏览器进行“导入到EXCEL”操作！');
           	}else{
           		materialAlert('提示','请使用IE浏览器进行“导入到EXCEL”操作！',function(result){
				});
           	}
             //方便设置安全等级，限制为ie浏览器  
           } 
       } 
	}
	else{
		var curTbl = document.getElementById('totalGrades').outerHTML; 
	    var excelHtml = "<html><head><meta charset='utf-8' /><style>td{border:1px #000 solid;border-collapse:collapse;} </style></head><body>"+curTbl+"</body></html>";
	    var excelBlob = new Blob([excelHtml], {type: 'application/vnd.ms-excel'})
	    // 创建一个a标签
	    var oA = document.createElement('a');
	    // 利用URL.createObjectURL()方法为a元素生成blob URL
	    oA.href = URL.createObjectURL(excelBlob);
	    // 给文件命名
	    oA.download = '本院系2018校史校情知识竞赛学生成绩.xls';
	    // 模拟点击
	    oA.click();
	    // 移除
	    oA.remove();

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
//----------------------------------------high charts----------------
var dataChart;
var dataChartData=[]


function updateDataChart(){

	var tot=0;
	var cnt_not=0;
	var cnt_0_60=0;
	var cnt_60_100=0;

	for(var i=0;i<grades_data.length;i++){
		if(grades_data[i][1]=="未完成")cnt_not++;
		else if(parseInt(grades_data[i][1])<60)cnt_0_60++;
		else if(parseInt(grades_data[i][1])>=60)cnt_60_100++;
		tot++;
	}

	dataChartData=[
	{
		name: '未完成',
		y: cnt_not/tot,
		cnt: cnt_not,
		sliced: true,
		selected: true
	},
	{
		name: '不及格(0~59)',
		y: cnt_0_60/tot,
		cnt: cnt_0_60
	},
	{
		name: '及格(60~100)',
		y: cnt_60_100/tot,
		cnt: cnt_60_100
	}
	];





	dataChart.series[0].setData(dataChartData);

}

function initDataChart(){
	// Make monochrome colors and set them as default for all pies
Highcharts.getOptions().plotOptions.pie.colors = (function () {
	var colors = [],
		base = Highcharts.getOptions().colors[0],
		i;
	for (i = 0; i < 10; i += 1) {
		// Start out with a darkened base color (negative brighten), and end
		// up with a much brighter color
		colors.push(Highcharts.Color(base).brighten((i - 3) / 7).get());
	}
	return colors;
}());
// 初始化图表
dataChart = Highcharts.chart('data_hc', {
	title: {
		text: '本院系完成情况'
	},
	credits: {
		enabled: false
	},
	tooltip: {
		pointFormat: '<b>{point.cnt}人</b>'
	},
	plotOptions: {
		pie: {
			allowPointSelect: true,
			cursor: 'pointer',
			dataLabels: {
				enabled: true,
				format: '<b>{point.name}</b>: {point.percentage:.1f} %',
				style: {
					color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
				}
			}
		}
	},
	series: [{
		type: 'pie',
		name: '答题情况',
		data: []
	}]
});
}