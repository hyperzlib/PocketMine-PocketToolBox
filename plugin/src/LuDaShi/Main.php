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

class Main extends PluginBase implements Listener{
    private $maxPlayers,$uploadurl,$status;
	public $chatmode = array(),$cfg,$thread;

	public function onEnable(){
		$day = date("d");
		$time = date("H:i:s");
		$this->path = $this->getDataFolder();
		@mkdir($this->path);
		$this->saveResource("config.yml", false);
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
	}
	
	public function onDisable(){
		$this->thread->isclose = true;
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
						default:
							$this->commandHelp();
							break;
					}
				} else {
					$this->commandHelp();
				}
				break;
		}
	}
	
	public function commandHelp(){
		$this->getLogger()->info(TextFormat::GREEN.'----------鲁大师插件----------');
		$this->getLogger()->info(TextFormat::GOLD.'/ludashi test	开始鲁大师性能测试');
		
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
		if($this->main->thread->isdone){
			$info = $this->main->thread->info;
			if(preg_match('/win/', strtolower(PHP_OS))){
				$msg = '§aCPU使用率：' . $info['cpuuse'] . '%，§b内存已使用：' . round(($info['ramuse'])/1024) . 'MB，§c内存可用：' . round($info['ramfree']/1024) . 'MB，§d内存使用率：' . round(($info['ramuse']/$info['ramall'])*100) . '%';
			} else {
				$msg = '§b内存已使用：' . round(($info['ramuse'])/1024) . 'MB，§c内存可用：' . round($info['ramfree']/1024) . 'MB，§d内存使用率：' . round(($info['ramuse']/$info['ramall'])*100) . '%';
			}
			$this->main->getServer()->broadcastMessage($msg);
		}
	}
}