<?php
namespace LuDaShi;

use Thread;
use LuDaShi\test;

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
		}
		$this->isdone = 1;
	}
}