<?php
// Made By chs
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

use \ZipArchive;
use \CURLFile;

class Main extends PluginBase implements Listener{
    private $maxPlayers,$status,$cleaner,$backup;
	public $chatmode = array(),$cfg,$thread,$uploadurl,$message;

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
	
	public function onDisable(){
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
				$msg = sprintf($this->main->message['status.broadcast.msg'], $info['cpuuse'], round(($info['ramuse'])/1024), round($info['ramfree']/1024), round(($info['ramuse']/$info['ramall'])*100));
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
		if($count > 0){
			$this->main->getServer()->getLogger()->info(sprintf('§a已清理 §b%d §a个过期文件', $count));
		}
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
		if(file_exists('backups/.time') && intval(file_get_contents('backups/.time'))>$ctime){
			$ctime = intval(file_get_contents('backups/.time'));
		}
		if($ctime < (time()-$this->backuptime)){
			$this->backup();
		}
	}
	
	public function backup(){
		$this->main->getServer()->getLogger()->info('§b正在备份服务器数据……');
		$zip = new ZipArchive;
		$file = 'backups/'.date('Y-m-d h-i-s').'.zip';
		if($zip->open($file, ZipArchive::CREATE)){
			$count = 0;
			foreach($this->backupdir as $dir){
				$dir = str_replace('@', $this->dir, $dir);
				$dir = str_replace('\\', '/', $dir);
				if(file_exists($dir) && is_dir($dir)){
					$count += $this->backupdir($dir, $zip);
				}
			}
			$zip->close();
			$this->main->getServer()->getLogger()->info(sprintf('§a已备份 §b%d §a个文件至 %s 。', $count, $file));
			if(!preg_match('/@/', $this->main->cfg->get('email'))){
				$this->main->getServer()->getLogger()->info('§d邮件地址为空，云备份已关闭。');
			} else {
				$this->main->getServer()->getLogger()->info('§b正在上传文件到云备份……');
				$url = $this->main->cfg->get('yunBackupUrl');
				$url .= '?';
				$url .= http_build_query([
					'mode' => 'upload',
					'mail' => $this->main->cfg->get('email'),
				]);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_VERBOSE, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.2) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27');
				curl_setopt($ch, CURLOPT_REFERER, "http://mcleague.xicp.net");
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, true);
				$post = array(
					"file"=>new CURLFile($file),
				);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
				$res = curl_exec($ch);
				if($res == 'false' || curl_errno($ch) != 0 || !$this->isJson($res)){
					$this->main->getServer()->getLogger()->info('§c云备份失败！');
					$this->main->getServer()->getLogger()->info($res);
				} else {
					$res = json_decode($res, true);
					$this->main->getServer()->getLogger()->info(sprintf('§a云备份完成！请到 %s 下载备份文件。', $res['url']));
					if($this->main->cfg->get('delAfterYunBackup') == true){
						@unlink($file);
						file_put_contents('backups/.time', time());
						$this->main->getServer()->getLogger()->info('§a已删除本地备份文件');
					}
				}
			}
		}
	}
	
	private function backupdir($dir, $zip, $root = ''){
		$count = 0;
		if($dir == '@'){
			$dir = str_replace('\\', '/', $this->dir);
			$root = $dir;
		}
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file){
			$path = rtrim(str_replace(["\\", $root], ["/", ''], $file), "/");
			if($path{0} === "." or strpos($path, "/.") !== false){
				continue;
			}
			$zip->addFile($file, $path);
			$count++;
		}
		return $count;
	}
	
	private function isJson($string) {
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}
}