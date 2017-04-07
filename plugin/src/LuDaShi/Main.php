<?php
// Made By chs
namespace LuDaShi;

use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;

use LuDaShi\test;
use LuDaShi\Status;

use \ZipArchive;

class Main extends PluginBase implements Listener{
    private $maxPlayers,$status,$cleaner,$backup;
	public $chatmode = array(),$cfg,$thread,$uploadurl;

	public function onEnable(){
		@unlink('.stop');
		$day = date("d");
		$time = date("H:i:s");
		$this->path = $this->getDataFolder();
		@mkdir($this->path);
		@mkdir('backups');
		$this->saveResource("config.yml", false);
		$this->saveResource("status.vbs", false);
		$this->cfg = new Config($this->getDataFolder()."config.yml",Config::YAML,array());
		$uname = php_uname();
		$this->getLogger()->info(TextFormat::AQUA.'鲁大师插件已启动');
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
	
	public function onDisable(){
		$this->thread->stop();
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		switch($command->getName()){
			case 'ludashi':
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
								$this->getLogger()->info(TextFormat::RED.'检测到服务器超开，超开比例：' . $progress . '%！');
							}
							return true;
							break;
						case 'status':
							$info = $this->thread->info;
							if($info['ramuse']!=0){
								$msg = '§aCPU使用率：' . $info['cpuuse'] . '%，§b内存已使用：' . round(($info['ramuse'])/1024) . 'MB，§c内存可用：' . round($info['ramfree']/1024) . 'MB，§d内存使用率：' . round(($info['ramuse']/$info['ramall'])*100) . '%';
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
		$this->getLogger()->info(TextFormat::GREEN.'----------鲁大师插件----------');
		$this->getLogger()->info(TextFormat::GOLD."/ludashi test		开始鲁大师性能测试");
		$this->getLogger()->info(TextFormat::GOLD."/ludashi ischaokai	鲁大师超开判断");
		$this->getLogger()->info(TextFormat::GOLD."/ludashi status	 获取当前服务器状态");
	}
		
}


class ServerStatus extends PluginTask{
	
	public $main,$broadtime,$ramall,$ramfree,$ramuse,$cpuuse,$plugin;
	
	public function __construct(Main $main){
		parent::__construct($main);
		$this->main = $main;
		$this->broadtime = $this->main->cfg->get('broadtime');
	}
	
	public function onRun($currentTick){
		while(!$this->main->thread->isdone){
			sleep(1);
		}
		if($this->main->thread->isdone){
			$info = $this->main->thread->info;
			if($info['ramuse']!=0){
				$msg = '§aCPU使用率：' . $info['cpuuse'] . '%，§b内存已使用：' . round(($info['ramuse'])/1024) . 'MB，§c内存可用：' . round($info['ramfree']/1024) . 'MB，§d内存使用率：' . round(($info['ramuse']/$info['ramall'])*100) . '%';
				$this->main->getServer()->broadcastMessage($msg);
			}
		}
	}
}

class AutoCleaner extends PluginTask{
	private $main,$cleantime,$dusttime,$dustdir;
	
	public function __construct(Main $main){
		parent::__construct($main);
		$this->main = $main;
		$this->cleantime = $this->main->cfg->get('cleantime');
		$this->dusttime = intval($this->main->cfg->get('dusttime'))*24*3600;
		if(preg_match('/,/', $this->main->cfg->get('dustdir'))){
			$this->dustdir = explode(',', $this->main->cfg->get('dustdir'));
		} else {
			$this->dustdir = array($this->main->cfg->get('dustdir'));
		}
	}
	
	public function onRun($currentTick){
		$count = 0;
		foreach($this->dustdir as $dir){
			if(file_exists($dir) && is_dir($dir)){
				$count += $this->cleandir($dir);
			}
		}
		$this->main->getServer()->broadcastMessage('§a已清理 §d'.$count.' §a个过期文件');
	}
	
	private function cleandir($dir){
		$count = 0;
		foreach(glob($dir . '/*') as $file){
			if(filectime($file)<time()-($this->dusttime)){
				@unlink($file);
				$count ++;
			}
		}
		return $count;
	}
}


class AutoBackup extends PluginTask{
	private $main,$backuptime,$backupdir,$dir;
	
	public function __construct(Main $main){
		parent::__construct($main);
		$this->main = $main;
		$this->backuptime = intval($this->main->cfg->get('backuptime'))*24*3600;
		if(preg_match('/,/', $this->main->cfg->get('backupdir'))){
			$this->backupdir = explode(',', $this->main->cfg->get('backupdir'));
		} else {
			$this->backupdir = array($this->main->cfg->get('backupdir'));
		}
		$this->dir = dirname(dirname($this->main->getDataFolder()));
	}
	
	public function onRun($currentTick){
		$ctime = 0;
		foreach(glob('backups/*.zip') as $file){
			$file = $this->dir . '/' . $file;
			if(filectime($file)>$ctime){
				$ctime = filectime($file);
			}
		}
		if($ctime < (time()-$this->backuptime)){
			$this->main->getServer()->broadcastMessage('§b正在备份服务器数据……');
			$zip = new ZipArchive;
			$file = 'backups/'.date('Y-m-d h-i-s').'.zip';
			if($zip->open($file, ZipArchive::CREATE)){
				$count = 0;
				foreach($this->backupdir as $dir){
					$dir = str_replace(['\\', '@'],['/', $this->dir],$dir);
					if(file_exists($dir) && is_dir($dir)){
						$count += $this->backupdir($dir, $zip);
					}
				}
				$zip->close();
				$this->main->getServer()->broadcastMessage('§a已备份 §d'.$count.' §a个文件至 '.$file.'。');
			}
		}
	}
	
	public function backup(){
		$this->main->getServer()->broadcastMessage('§b正在备份服务器数据……');
		$zip = new ZipArchive;
		$file = 'backups/'.date('Y-m-d h-i-s').'.zip';
		if($zip->open($file, ZipArchive::CREATE)){
			$count = 0;
			foreach($this->backupdir as $dir){
				$dir = str_replace(['\\', '@'],['/', $this->dir],$dir);
				if(file_exists($dir) && is_dir($dir)){
					$count += $this->backupdir($dir, $zip);
				}
			}
			$zip->close();
			$this->main->getServer()->broadcastMessage('§a已备份 §d'.$count.' §a个文件至 '.$file.'。');
		}
	}
	
	private function backupdir($dir, $zip){
		$count = 0;
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file){
			$path = rtrim(str_replace(["\\"], ["/"], $file), "/");
			if($path{0} === "." or strpos($path, "/.") !== false){
				continue;
			}
			$zip->addFile($file, $path);
			$count++;
		}
		return $count;
	}
}