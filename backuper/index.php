<?php
include('mail.php');
if($_GET['mode']=='upload'){  //上传模式
	$id = md5($_FILES["file"]["name"].microtime());
	$dir = 'files/'.str_replace(['/', '\\'], ['', ''], $_GET['mail']);
	if(!file_exists($dir) || !is_dir($dir))
	mkdir($dir);
	copy($_FILES["file"]["tmp_name"], $dir.'/'.$_FILES["file"]["name"]);
	if(mailto($_GET['mail'], 'http://liangzi.xicp.net/backup/?mode=download&file='.urlencode($_FILES["file"]["name"]).'&mail='.$_GET['mail'])){
		echo json_encode(array('url'=>'http://liangzi.xicp.net/backup/?mode=download&file='.urlencode($_FILES["file"]["name"]).'&mail='.$_GET['mail'], 'file'=>urlencode($_FILES["file"]["name"])));
	} else {
		echo 'false';
	}
} elseif($_GET['mode']=='download') {
	$file = 'files/'.str_replace(['/', '\\'], ['', ''], $_GET['mail']).'/'.str_replace(['/', '\\'], ['', ''], $_GET['file']);
	if(file_exists($file)){
		header('Content-Type:text/plain'); //发送指定文件MIME类型的头信息
		header('Content-Disposition:attachment; filename="备份 '.basename($file).'"'); //发送描述文件的头信息，附件和文件名
		header('Content-Length:'.filesize($file)); //发送指定文件大小的信息，单位字节
		readfile($file);
	} else {
		header("HTTP/1.1 404 Not Found");  
		header("Status: 404 Not Found");  
		echo '404 Not Found!';
	}
}