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
use Thread;
use PocketToolBox\test;
class ServerTest extends Thread{
	private $portmin,$portmax,$c,$isdone = 0,$pcount = 0;
	
	public function __construct($portmin, $portmax, $c){
		$this->portmin = $portmin;
		$this->portmax = $portmax;
		$this->c = $c;
	}
	
	public function run(){
		$test = new test;
		$msg = 'AQAAAAAAAASyAP//AP7+/v79/f39EjRWeP////+SQrzp';
		$msg = base64_decode($msg);
		$len = strlen($msg);
		for($port = $this->portmin; $port < $this->portmax; $port++){
			$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
			socket_set_option($sock,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>1, "usec"=>0 ) );
			socket_set_option($sock,SOL_SOCKET,SO_SNDTIMEO,array("sec"=>1, "usec"=>0 ) );
			socket_sendto($sock, $msg, $len, 0, '127.0.0.1', $port);
			@socket_recvfrom($sock, $buf, 1024, 0, $from, $port);
			if(is_string($buf) && preg_match('/MCPE/',$buf)){
				$this->pcount++;
				sleep(3);
			}
			socket_close($sock);
		}
		$this->isdone = 1;
	}
}