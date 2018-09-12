<?php
session_start();

$image = imagecreatetruecolor(100, 30); 
$bgcolor = imagecolorallocate($image, 255, 255, 255); 
imagefill($image, 0, 0, $bgcolor); 


$captch_code="";  
for($i=0; $i<4; $i++){  
	$fontsize=90;  
	$fontcolor=imagecolorallocate($image, rand(0,120), rand(0,120), rand(0, 120));  
	$data="23456789abcdefghkmnpqrstwxyzABCDEFGHGKLMNPQRSTWXYZ";
        //设置每次产生的字符从$data中每次截取一个字符  
	$fontcontent=substr($data, rand(0,strlen($data)-1), 1);  
        //让产生的四个字符拼接起来  
	$captch_code.=$fontcontent;  
        //控制每次出现的字符的坐标防止相互覆盖即x->left y->top  
	$x=($i*100/4)+rand(5, 10);  
	$y=rand(5, 10);  
        //此函数用来将产生的字符在背景图上画出来  
	imagestring($image, $fontsize, $x, $y, $fontcontent, $fontcolor);  
}  

for($i=0; $i<200; $i++){  
        //干扰点的颜色  
	$pointcolor=imagecolorallocate($image, rand(50,200), rand(50, 200), rand(50, 200));  
        //该函数用来把每个干扰点在背景上描绘出来  
	imagesetpixel( $image, rand(1, 99), rand(1,29), $pointcolor);  
}  

    //产生三条干扰线  
for ($i=0; $i <3 ; $i++) {   
        # code...  
        //干扰线的颜色  
	$linecolor=imagecolorallocate($image, rand(80, 220), rand(80, 220), rand(80, 220));  
        //画出每条干扰线  
	imageline($image, rand(1, 99), rand(1, 29), rand(1, 99), rand(1,29), $linecolor);  
} 


$_SESSION['captch']=$captch_code;


ob_start ();
//$im是你自己创建的图片资源
imagepng ($image);

$image_data = ob_get_contents ();

ob_end_clean ();

//得到这个结果，可以直接用于前端的img标签显示
$image_data_base64 = "data:image/png;base64,". base64_encode ($image_data);
echo $image_data_base64;

imagedestroy($image);


?>