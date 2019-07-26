<?php

class MFailover {
        public $primary_ip;
        public $new_primary_ip;
        public $ssh_port;
        public $vip;
        public $network_name;
        
        function __construct($primary_ip,$new_primary_ip,$ssh_port,$vip,$network_name){
                $this->primary_ip = $primary_ip;
                $this->new_primary_ip = $new_primary_ip;
                $this->ssh_port = $ssh_port;
                $this->vip = $vip;
                $this->network_name = $network_name;
        }       
        
        function execCommand(){
                $del_vip="sudo /usr/bin/ssh -p $this->ssh_port root@$this->primary_ip -C '/sbin/ip addr del {$this->vip}/32 dev $this->network_name'";
		system("$del_vip");
                sleep(1);  
                $add_vip="sudo /usr/bin/ssh -p $this->ssh_port root@$this->new_primary_ip -C '/sbin/ip addr add {$this->vip}/32 dev $this->network_name;/sbin/arping -q -c 2 -U -I $this->network_name $this->vip'";
		system("$add_vip",$res);                
		if($res == 0){
			file_put_contents(dirname(__FILE__)."/".strstr($GLOBALS['filename'],'.',true)."_master_status.health", date('Y-m-d H:i:s')."\n\n"."VIP: {$this->vip}已经切换成功"."\n\n", FILE_APPEND);
                        echo "VIP: {$this->vip}已经切换成功".PHP_EOL.PHP_EOL;
			exit;
                } else {
			file_put_contents(dirname(__FILE__)."/".strstr($GLOBALS['filename'],'.',true)."_master_status.health", date('Y-m-d H:i:s')."\n\n"."VIP: {$this->vip}切换失败，退出主程序!"."\n\n", FILE_APPEND);
                        echo "VIP: {$this->vip}切换失败，退出主程序!".PHP_EOL.PHP_EOL;
                        exit;
                } 
        }       
}

?>

