<?php
namespace LuDaShi;

use Thread;

class Status extends Thread{
	
	public $main,$broadtime,$info = array(),$isdone = false;
	public $isclose = false;
	
	public function run(){
		if(preg_match('/win/', strtolower(PHP_OS))){
			if(!file_exists(dirname(dirname(dirname(dirname(__FILE__)))).'\\LuDaShi\\status.vbs')){
				copy(dirname(__FILE__).'\\status.vbs', dirname(dirname(dirname(dirname(__FILE__)))).'\\LuDaShi\\status.vbs');
			}
			$status = popen('cscript '.dirname(dirname(dirname(dirname(__FILE__)))).'\\LuDaShi\\status.vbs', 'r');
			fgets($status, 1024);
			fgets($status, 1024);
			fgets($status, 1024);
			$time = 0;
			do{
				if($this->isclose!=false){
					pclose($status);
					break;
				}
				
				$info = json_decode(fgets($status, 1024), true);
				$this->info['ramuse'] = $info['ramall'] - $info['ramfree'];
				$this->info['ramall'] = $info['ramall'];
				$this->info['ramfree'] = $info['ramfree'];
				$this->info['cpuuse'] = $info['cpu'];
				$this->isdone = true;
			}while(true);
		} else {
			do{
				$str = @file_get_contents('/proc/meminfo');
				preg_match_all("/MemTotal\s{0,}\:+\s{0,}([\d\.]+).+?MemFree\s{0,}\:+\s{0,}([\d\.]+).+?Cached\s{0,}\:+\s{0,}([\d\.]+).+?SwapTotal\s{0,}\:+\s{0,}([\d\.]+).+?SwapFree\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buf);
				preg_match_all("/Buffers\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buffers);
				$this->info['ramall'] = $buf[1][0];
				$this->info['ramfree'] = $buf[2][0];
				$this->info['ramuse'] = $buf[1][0]-$buf[2][0];
				$this->info['cpuuse'] = '不支持';
				$this->isdone = true;
				sleep(2);
			}while($this->isclose==false);
		}
	}
}