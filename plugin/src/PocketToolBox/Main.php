<?php
/*
 *
 *  ____             _         _   _____             _ ____ 
 * |  _ \  ___   ___| | __ ___| |_|_   _| ___   ___ | | __ )  ___ __  __
 * | |_) |/ _ \ / __| |/ // _ \ __| | |  / _ \ / _ \| |  _ \ / _ \\ \/ /
 * |  __/| (_) | (__|   <|  __/ |_  | | | (_) | (_) | | |_) | (_) |)  ( 
 * |_|    \___/ \___|_|\_\\___|\__| |_|  \___/ \___/|_|____/ \___//_/\_\
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author chs
 * @github https://github.com/hyperzlib/PocketMine-PocketToolBox/
 * @website http://mcleague.xicp.net/
 *
 *
*/

namespace PocketToolBox;

use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;

use PocketToolBox\test;
use PocketToolBox\Status;
use PocketToolBox\AutoBackup;
use PocketToolBox\AutoCleaner;
use PocketToolBox\ServerStatus;

use \ZipArchive;
use \CURLFile;

class Main extends PluginBase implements Listener{
    private $maxPlayers,$status,$cleaner,$backup;
	public $chatmode = array(),$cfg,$thread,$uploadurl,$message,$php_path;

	public function onEnable(){
		@unlink('.stop');
		$day = date("d");
		$time = date("H:i:s");
		$this->path = $this->getDataFolder();
		if(file_exists($this->path.'/.crash')){
			$file = file_get_contents($this->path.'/.crash');
			$name = new Config($this->getDataFolder()."config.yml",Config::YAML,array());
			$name = $name->get('name');
			$plug = $this->getServer()->getPluginManager()->getPlugin($name);
			if($plug != false){
				$this->getServer()->getPluginManager()->disablePlugin($plug);
			}
			@unlink($file);
			@unlink($this->path.'/.crash');
		}
		@mkdir($this->path);
		@mkdir('backups');
		$this->saveResource("config.yml", false);
		$this->saveResource("status.vbs", false);
		$this->cfg = new Config($this->getDataFolder()."config.yml",Config::YAML,array());
		$uname = php_uname();
		$this->getLogger()->info(TextFormat::AQUA.'口袋工具箱已启动');
		$version = explode(' ', $this->getFullName());
		$version = str_replace('v', '', $version[1]);
		if($this->cfg->get('version') != $version){
			$this->saveResource("config.yml", true);
		}
		$data = @file_get_contents('http://git.oschina.net/hyperquantum/PocketMine-LuDaShi/raw/master/config.json?dir=0&filepath=config.json');
		if($data != ''){
			$data = json_decode($data, true);
			if($data['version'] != $version){
				$this->getLogger()->info(TextFormat::GOLD.$data['update']);
			}
			$this->uploadurl = $data['upload'];
		}
		restore_error_handler();
		set_error_handler([$this, 'crashDumpFunction']); //设置错误捕获器
		$this->message = $this->cfg->get('message');
		$this->thread = new Status();
		$this->thread->start();
		$this->status = new ServerStatus($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->status, $this->cfg->get('broadtime')*20);
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		
		$this->cleaner = new AutoCleaner($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->cleaner, $this->cfg->get('broadtime')*3600*24*20);
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
		
		$this->backup = new AutoBackup($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask($this->backup, 3600);
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
	}
	
	public function crashDumpFunction ($error_level,$error_message,$error_file,$error_line,$error_context){
		static $crashDumpFunction = true;
		if($crashDumpFunction){
			if($error_level == E_NOTICE){
				$e = error_get_last(); 
				if($e['type'] == 64){
					file_put_contents('.stop', time());
					$dir = 'plugins/crashPlugins';
					$file = $e['file'];
					if(!file_exists($dir) || !is_dir($dir)){
						@mkdir($dir);
					}
					if(preg_match('/\.phar/', $file)){
						$file = str_replace('phar://', '', explode('.phar', $file)[0] . '.phar');
						if(!file_exists($dir.'/'.basename($file))){
							copy($file, $dir.'/'.basename($file));
						}
						unlink($file);
						file_put_contents('plugins/PocketToolBox/.crash', $file);
					}
					$mail = $this->cfg->get('email');
					if($mail != ''){
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_VERBOSE, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.2) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27');
						curl_setopt($ch, CURLOPT_REFERER, "http://mcleague.xicp.net/site/pl/crash/mail.php");
						curl_setopt($ch, CURLOPT_URL, $url);
						curl_setopt($ch, CURLOPT_POST, true);
						$post = array(
							'mail' => $mail,
							'file'=>$file,
						);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
						curl_exec($ch);
					}
					$crashDumpFunction = false;
					if(count(glob('../*.pid')) == 0 && $this->cfg->get('autoReboot') == true){
						$this->getServer()->getLogger()->info(TextFormat::AQUA.'正在准备自动重启……');
						if(preg_match('\.phar', \pocketmine\PATH)){
							$pm = str_replace('phar://', '', rtrim(str_replace('\\', '/', \pocketmine\PATH), '/'));
						} else {
							$pm = \pocketmine\PATH . 'src/pocketmine/PocketMine';
						}
						if(preg_match('/win/', strtolower(PHP_OS))){
							$p = popen('cmd', 'w');
							if($this->cfg->get('rebootCmd') != ''){
								if(file_exists('start.cmd')){
									fwrite($p, "start start.cmd\r\n");
								} elseif(file_exists('start.bat')){
									fwrite($p, "start start.bat\r\n");
								} else {
									$php = $this->real_path();
									$php = str_replace('/', '\\', $php);
									fwrite($p, 'start ' . $php . ' ' . $pm . "\r\n");
								}
							} else {
								fwrite($p, $this->cfg->get('rebootCmd') . "\r\n");
							}
							pclose($p);
						} elseif(preg_match('/lin/', strtolower(PHP_OS))){
							if($this->cfg->get('rebootCmd') != ''){
								if(file_exists('start.sh')){
									system('start.sh &');
								} else {
									system($php . ' ' . $pm . '&');
								}
							} else {
								system($this->cfg->get('rebootCmd') . ' &');
							}
						}
					}
				}
			}
		}
	}

	public function real_path() {
        if ($this->php_path != '') {
            return $this->php_path;
        }
        if (substr(strtolower(PHP_OS), 0, 3) == 'win') {
            $ini = ini_get_all();
            $path = $ini['extension_dir']['local_value'];
            $php_path = str_replace('\\', '/', $path);           
            $php_path = str_replace(array('/ext/', '/ext'), array('/', '/'), $php_path);           
            $real_path = $php_path . 'php.exe';       
        } else {           
            $real_path = PHP_BINDIR . '/php';       
        }
        if (strpos($real_path, 'ephp.exe') !== FALSE) {           
            $real_path = str_replace('ephp.exe', 'php.exe', $real_path);  
        }       
        $this->php_path = $real_path;       
        return $this->php_path;   
    }
	
	public function onDisable(){
		restore_error_handler();
		$this->thread->stop();
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch($command->getName()){
			case 'ptb':
				if(isset($args[0])){
					switch($args[0]){
						case 'test':
							$test = new test;
							$test->start($this);
							return true;
							break;
						case 'ischaokai':
							$memlimit = intval(str_replace('M','',ini_get('memory_limit')))*1024;
							$memfree = $memlimit - memory_get_usage();
							if(($this->thread->info['ramfree']) > $memfree){
								$this->getLogger()->info(TextFormat::GREEN.'你的服务器看样子没有超开。');
							} else {
								$ramtruefree = $this->thread->info['ramfree'];
								$progress = round((1 - (($ramfree - $ramtruefree) / $ramfree))*100);
								$this->getLogger()->info(sprintf(TextFormat::RED.'检测到服务器超开，超开比例：%d%%！', $progress));
							}
							return true;
							break;
						case 'status':
							$info = $this->thread->info;
							if($info['ramuse']!=0){
								$msg = sprintf($this->message['status.broadcast.msg'], $info['cpuuse'], round(($info['ramuse'])/1024), round($info['ramfree']/1024), round(($info['ramuse']/$info['ramall'])*100));
								$this->getLogger()->info($msg);
							}
							return true;
							break;
						case 'backup':
							$this->backup->backup();
							return true;
							break;
						default:
							$this->commandHelp();
							return true;
							break;
					}
				} else {
					$this->commandHelp();
					return true;
				}
				break;
		}
	}
	
	public function commandHelp(){
		$this->getLogger()->info(TextFormat::GREEN.'----------PocketToolBox----------');
		$this->getLogger()->info(TextFormat::GOLD."/ptb test		开始性能测试");
		$this->getLogger()->info(TextFormat::GOLD."/ptb ischaokai	超开判断");
		$this->getLogger()->info(TextFormat::GOLD."/ptb status	 	获取当前服务器状态");
		$this->getLogger()->info(TextFormat::GOLD."/ptb backup	 	备份服务器");
	}
		
}