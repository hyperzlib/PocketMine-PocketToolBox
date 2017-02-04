<?php
namespace LuDaShi;

use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use LuDaShi\ServerTest;

class test{
	public $pcount = array(),$isdone = array(),$ip;
	
	public function start($main){
		$ip = @file_get_contents('http://ip.chinaz.com/getip.aspx');
		$ip = str_replace('\'','"',$ip);
		$ip = str_replace(array('ip','address'),array('"ip"','"address"'),$ip);
		$ip = json_decode($ip, true);
		$this->ip = $ip['ip'];
		
		$point = array();
		
		//内存测试区
		$main->getLogger()->info(TextFormat::AQUA."开始内存测试……");
		$memory = array();
		$ram = ini_get('memory_limit');
		$ram = str_replace('M','',$ram);
		$ram = intval($ram)*1024*1024;
		$memory['limit'] = memory_get_peak_usage();
		unset($ram);
		$str = '';
		$count = 0;
		while(memory_get_usage() < $memory['limit']){
			$str .= str_repeat('a', 1024);
		}
		$memory['max'] = memory_get_usage()/1024/1024;
		unset($str);
		
		//cpu测试区
		$timeuse = array();
		$main->getLogger()->info(TextFormat::AQUA."开始CPU测试");
		//1.整数计算
		$timestart = gettimeofday();
		$t = 0;
		for($i=0;$i<=9000000;$i++){
			$t = 1+$t;
			if($t == 32767){
				$t = 0;
			}
		}
		$timestop = gettimeofday();
		$timeuse[0] = ($timestop["usec"]-$timestart["usec"])/1000000+$timestop["sec"]-$timestart["sec"];
		
		//2.浮点计算
		$t = pi();
		$r = 1;
		$timestart = gettimeofday();
		for($i=0;$i<=9000000;$i++){
			sqrt($t) + $r;
			$r *= -1;
		}
		$timestop = gettimeofday();
		$timeuse[1] = ($timestop["usec"]-$timestart["usec"])/1000000+$timestop["sec"]-$timestart["sec"];
		
		$point['cpu'] = 10000 - round($timeuse[0]*100) + 10000 - round($timeuse[1]*100);
		unset($timeuse);
		
		//硬盘IO测试区
		$speed = array();
		$main->getLogger()->info(TextFormat::AQUA."开始磁盘速度测试");
		//1.写入
		$str = str_repeat('a', 1024*1024);
		$timestart = gettimeofday();
		$fp = fopen('test.txt','w');
		for($i=0;$i<=1024;$i++){
			fwrite($fp, $str);
		}
		fclose($fp);
		$timestop = gettimeofday();
		$timeuse = ($timestop["usec"]-$timestart["usec"])/1000000+$timestop["sec"]-$timestart["sec"];
		$speed[0] = 1024/$timeuse;
		
		//2.读取
		$timestart = gettimeofday();
		$fp = fopen('test.txt','r');
		for($i=0;$i<=1024;$i++){
			$str = fread($fp, 1024*1024);
		}
		fclose($fp);
		$timestop = gettimeofday();
		$timeuse = ($timestop["usec"]-$timestart["usec"])/1000000+$timestop["sec"]-$timestart["sec"];
		$speed[1] = 1024/$timeuse;
		
		//3.删除
		$timestart = gettimeofday();
		@unlink('test.txt');
		$timestop = gettimeofday();
		$timeuse = ($timestop["usec"]-$timestart["usec"])/1000000+$timestop["sec"]-$timestart["sec"];
		$speed[2] = 1024/$timeuse;
		
		unset($str);
		$point['io'] = round($speed[0]*10) + round($speed[1]*10) + round($speed[2]*10);
		unset($speed);
		
		//同服务器mc服数量测试
		$main->getLogger()->info(TextFormat::AQUA."即将开始同服务器mc服数量测试，可能会有一段时间卡死。");
		//算法1
		sleep(5);
		$list = glob('../*.pid');
		if(count($list)>1){
			$point['server'] = (150 - intval(count($list))) * 10;
		} else { //算法2
			$port = $main->getServer()->getPort();
			$threads = array();
			$k = 0;
			$one = 90;
			for($i = 10000; $i< $port + 1000 - $one; $i+=$one){
				$main->getLogger()->info(TextFormat::GOLD."线程".$k.'就绪！');
				$threads[] = new ServerTest($i, $i+$one, $k);
				$k++;
			}
			
			//启动线程
			$k = 0;
			$ct=0;
			$trun=[];
			foreach($threads as $thread){
				$main->getLogger()->info(TextFormat::AQUA."线程".$k.'已启动！');
				$thread->start();
				$k ++;
				$ct++;
				$trun[]=$thread;
				if($ct>10){
					$ct=0;
					foreach($trun as $t){$t->join();}
					$trun=[];
				}
			}
			unset($trun);
			//回收线程
			$done = false;
			do{
				$done = true;
				foreach($threads as $thread){
					if($thread->isdone == 0){
						$done = false;
					} elseif($thread->isdone == 1) {
						$main->getLogger()->info(TextFormat::GOLD."线程".$thread->c."已返回！");
						$thread->isdone = 2;
					}
					
				}
				sleep(1);
			}while(!$done);
			$count = 0;
			$k = 0;
			foreach($threads as $thread){
				$count += $thread->pcount;
				$k ++;
			}
		}
		$point['server'] = (150 - $count)*10;
		
		//开始网速测试
		$speed = array();
		$main->getLogger()->info(TextFormat::AQUA."开始网络速度测试");
		
		//上传速度
		$main->getLogger()->info(TextFormat::GOLD."上传测试……");
		$url = "http://www.baidu.com/";
		$post_data = array(
			"foo" => "bar",
			"upload" => str_repeat('a',1024*1024),
		);
		$ch = curl_init();
		curl_setopt($ch , CURLOPT_URL , $url);
		curl_setopt($ch , CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch , CURLOPT_POST, 1);
		curl_setopt($ch , CURLOPT_POSTFIELDS, $post_data);

		$timestart = gettimeofday();
		$output = curl_exec($ch);
		$timestop = gettimeofday();
		$timeuse = ($timestop["usec"]-$timestart["usec"])/1000000+$timestop["sec"]-$timestart["sec"];

		curl_close($ch);
		$speed[0] = 1024/$timeuse;
		
		//下载速度
		$main->getLogger()->info(TextFormat::GOLD."下载测试……");
		$url = "http://www.baidu.com/";
		$ch = curl_init();
		curl_setopt($ch , CURLOPT_URL , $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		$timestart = gettimeofday();
		$output = curl_exec($ch);
		$timestop = gettimeofday();
		$timeuse = ($timestop["usec"]-$timestart["usec"])/1000000+$timestop["sec"]-$timestart["sec"];

		curl_close($ch);
		$speed[1] = strlen($output)/1024/$timeuse;
		
		$point['web'] = ($speed[0]*10) + ($speed[1]*10);
		
		$main->getLogger()->info(TextFormat::GOLD."测试完成！cpu得分：" . ($point['cpu']) . ',硬盘得分：' . ($point['io']) . ',同服mc服数量得分：' . ($point['server']) . ',网速得分：' . ($point['web']) . ',总分：' . (intval($point['cpu']+$point['io']+$point['server']+$point['web'])));
		$query = $point;
		$query['ip'] = $this->ip;
		$query['os'] = PHP_OS;
		
		//获取cpu型号
		if(preg_match('/win/', strtolower(PHP_OS))){
			$cpus = explode("\r\n",shell_exec('wmic cpu list brief'));
			$cpus[0] = '';
			$cpulist = array();
			foreach($cpus as $cpu){
				if($cpu != ''){
					$array = explode('  ',$cpu);
					$array = array_filter($array);
					$cpusinfo = array();
					foreach($array as $one){
						$cpuinfo[] = trim($one);
					}
					$cpulist[] = $cpuinfo[4];
					unset($cpuinfo);
					unset($array);
				}
			}
		} else {
			$cpu = file_get_contents('/proc/cpuinfo');
			$cpulist = array();
			preg_match('/model name[ ]{0,100}.*/',$cpu,$match);
			$cpulist[] = trim(str_replace(array('model name',':'),'',$match[0]));
		}
		
		$other = array(
			'web_upload'=>$speed[0],
			'web_download'=>$speed[1],
			'uname'=>php_uname(),
			'cpulist'=>$cpulist,
			'add'=>array(array('服务器端口',$main->getServer()->getPort())),
		);
		$query['other'] = json_encode($other);
		$query = http_build_query($query);
		$web = @file_get_contents($main->uploadurl.'?'.$query);
		$main->getLogger()->info(TextFormat::AQUA."向服务器同步成功，".$web);
		return $point;
	}
}
