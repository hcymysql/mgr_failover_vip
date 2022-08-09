<?php

class Check_primary {
	public $primary_ip;
	public $new_primary_ip;
	public $user;
	public $passwd;
	public $port;
	public $new_port;
	
	function __construct($primary_ip,$new_primary_ip,$user,$passwd,$port,$new_port,$hostname,$status){
		$this->primary_ip = $primary_ip;
		$this->new_primary_ip = $new_primary_ip;	
		$this->user = $user;
		$this->passwd = $passwd;
		$this->port = $port;
		$this->new_port = $new_port;
		$this->hostname = $hostname;
		$this->status = $status;
	}	
	
	function check_primary(){
		if($this->status == 0){
			$con = mysqli_connect("{$this->primary_ip}","{$this->user}","{$this->passwd}","performance_schema","{$this->port}");
			$p_ip = $this->primary_ip;
		} else {
			$con = mysqli_connect("{$this->new_primary_ip}","{$this->user}","{$this->passwd}","performance_schema","{$this->new_port}");
			$p_ip = $this->new_primary_ip;
		}
        	if(!mysqli_connect_errno($con)){
			file_put_contents(dirname(__FILE__)."/".strstr($GLOBALS['filename'],'.',true)."_master_status.health", date('Y-m-d H:i:s')."\n\n"."连接 Primary节点: $p_ip 成功"."\n\n", FILE_APPEND);
                	echo "连接 Primary节点: $p_ip 成功".PHP_EOL.PHP_EOL;
			mysqli_query($con,"set global group_replication_member_weight = 100");
                	$get_primary_info = "SELECT MEMBER_HOST,MEMBER_ROLE,MEMBER_STATE FROM performance_schema.replication_group_members WHERE MEMBER_ROLE = 'PRIMARY' AND MEMBER_STATE = 'ONLINE'";                
			$result = mysqli_query($con,$get_primary_info);
			if(mysqli_num_rows($result) > 0){
                        	$row = mysqli_fetch_array($result);
                        	if( $p_ip == $this->hostname[$row[0]]){
					file_put_contents(dirname(__FILE__)."/".strstr($GLOBALS['filename'],'.',true)."_master_status.health", date('Y-m-d H:i:s')."\n\n"."主机名: ".sprintf("%-15s",$row[0])."      角色: ".$row[1]."\n"."Primary IP: ".$p_ip."\n\n", FILE_APPEND);
					echo "主机名: ".sprintf("%-15s",$row[0])."      角色: ".$row[1].PHP_EOL;
					echo "Primary IP: ".$p_ip.PHP_EOL.PHP_EOL;
					
					//判断有无延迟事务
					mysqli_query($con,"set global group_replication_member_weight = 100");
					mysqli_query($con,"set global group_replication_consistency = 'BEFORE_ON_PRIMARY_FAILOVER'");
					$get_queue_count = "SELECT COUNT_TRANSACTIONS_REMOTE_IN_APPLIER_QUEUE FROM performance_schema.replication_group_member_stats WHERE MEMBER_ID IN (SELECT MEMBER_ID FROM performance_schema.replication_group_members WHERE MEMBER_ROLE = 'SECONDARY' AND MEMBER_STATE = 'ONLINE')";
					$result2 = mysqli_query($con,$get_queue_count);
					$row2 = mysqli_fetch_array($result2);
					if ($row2[0] == 0){
						file_put_contents(dirname(__FILE__)."/".strstr($GLOBALS['filename'],'.',true)."_master_status.health", date('Y-m-d H:i:s')."\n\n无数据延迟"."\n\n", FILE_APPEND);
						echo "无数据延迟".PHP_EOL;
						return $c=1;
					} else {
						file_put_contents(dirname(__FILE__)."/".strstr($GLOBALS['filename'],'.',true)."_master_status.health", date('Y-m-d H:i:s')."\n\n数据延迟 ".$row2[0]." 个事务，不能切换VIP"."\n\n", FILE_APPEND);
						echo "\e[38;5;196m数据延迟 ".$row2[0]." 个事务，不能切换VIP".PHP_EOL;
						exit();
					}
				} else {
					file_put_contents(dirname(__FILE__)."/".strstr($GLOBALS['filename'],'.',true)."_master_status.health", date('Y-m-d H:i:s')."\n\n"."检测到主节点{$this->primary_ip}当前已经不是Primary状态, 即将进行切换VIP...."."\n\n", FILE_APPEND);
                                	echo "\e[38;5;196m检测到主节点{$this->primary_ip}当前已经不是Primary状态, 即将进行切换VIP...".PHP_EOL.PHP_EOL;
					return $c=1;
                                }
			} else {
				file_put_contents(dirname(__FILE__)."/".strstr($GLOBALS['filename'],'.',true)."_master_status.health", date('Y-m-d H:i:s')."\n\n"."检测到主节点{$this->primary_ip}当前已经不是Primary状态, 即将进行切换VIP...."."\n\n", FILE_APPEND);
				echo "\e[38;5;196m检测到主节点{$this->primary_ip}当前已经不是Primary状态, 即将进行切换VIP...".PHP_EOL.PHP_EOL;
				return $c=1; 
			}
		} else{
			file_put_contents(dirname(__FILE__)."/".strstr($GLOBALS['filename'],'.',true)."_master_status.health", date('Y-m-d H:i:s')."\n\n"."检测到主主节点{$this->primary_ip}已经无法连接, 即将进行切换VIP..."."\n\n", FILE_APPEND);
			echo "\e[38;5;196m检测到主节点{$this->primary_ip}已经无法连接, 即将进行切换VIP...".PHP_EOL.PHP_EOL;
			return $c=1;
		}

	}	

}



?>
