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

use \ZipArchive;
use \CURLFile;

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