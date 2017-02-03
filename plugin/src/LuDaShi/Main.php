<?php
// Made By chs
namespace LuDaShi;

use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;

use LuDaShi\test;

class Main extends PluginBase implements Listener{
    private $maxPlayers,$uploadurl;
	public $chatmode = array();

	public function onEnable(){
		$day = date("d");
		$time = date("H:i:s");
		$this->path = $this->getDataFolder();
		//@mkdir($this->path);
		$uname = php_uname();
		$this->getLogger()->info(TextFormat::AQUA.'鲁大师插件已启动');
		$version = explode(' ', $this->getFullName());
		$version = str_replace('v', '', $version[1]);
		$data = @file_get_contents('http://git.oschina.net/hyperquantum/PocketMine-LuDaShi/raw/master/config.json?dir=0&filepath=config.json');
		if($data != ''){
			$data = json_decode($data, true);
			if($data['version'] != $version){
				$this->getLogger()->info(TextFormat::GOLD.$data['update']);
			}
			$this->uploadurl = $data['upload'];
		}
	}
	
	public function onDisable(){
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