<?php
namespace PocketToolBox;

use Thread;

class Status extends Thread{
	
	public $main,$broadtime,$info = array(),$isdone = false;
	public $isclose = false;
	
	public function run(){
		if(preg_match('/win/', strtolower(PHP_OS))){
			if(!file_exists(dirname(dirname(dirname(dirname(__FILE__)))).'\\PocketToolBox\\status.vbs')){
				copy(dirname(__FILE__).'\\status.vbs', str_replace('phar://', '',dirname(dirname(dirname(dirname(__FILE__))))).'\\PocketToolBox\\status.vbs');
			}
			$status = popen('cscript '.str_replace('phar://', '', dirname(dirname(dirname(dirname(__FILE__))))).'\\PocketToolBox\\status.vbs', 'r');
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
				$top = shell_exec('top -n 1 -b');
				$top = explode("\n",$top);
				$arr = array();
				foreach($top as $one){
					if(preg_match('/:/',$one)){
						$tmp = explode(':',$one);
						$arr[trim($tmp[0])] = explode(',',$tmp[1]);
					}
				}
				$this->info['cpuuse'] = intval(str_replace('%us','',trim($arr['Cpu(s)'][0])));
				$this->info['ramall'] = intval(str_replace('k total','',trim($arr['Mem'][0])));
				$this->info['ramuse'] = intval(str_replace('k used','',trim($arr['Mem'][1])));
				$this->info['ramfree'] = intval(str_replace('k free','',trim($arr['Mem'][2])));
				sleep(2);
			}while($this->isclose==false);
		}
	}
	
	public function stop(){
		$this->iscolse = true;
		file_put_contents('.stop', date("Y-m-d H:i:s"));
	}
}
