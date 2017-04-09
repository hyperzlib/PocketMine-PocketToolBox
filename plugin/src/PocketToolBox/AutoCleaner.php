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
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\scheduler\PluginTask;

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
