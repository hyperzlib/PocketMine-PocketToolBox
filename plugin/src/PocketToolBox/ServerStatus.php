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
