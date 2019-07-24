<?php

/**
 * MGR（组复制）高可用VIP切换脚本
 * https://github.com/hcymysql/mgr_failover_vip
 *
 * 运行条件:
 * Modified by: hcymysql 2019/07/24
 * 1、MySQL 8.0版本
 * 2、单主模式
 * 3、Native Password Authentication
 * 例：> CREATE USER 'hechunyang'@'%' IDENTIFIED WITH mysql_native_password BY '123456';
 *     > GRANT ALL ON *.* TO 'hechunyang'@'%' WITH GRANT OPTION;
 *
 * 环境准备: 
 * shell> yum install -y php-process php php-mysql
 *  
 */

ini_set('date.timezone','Asia/Shanghai');

function Usage(){
echo "\e[38;5;11m
 * MGR（组复制）高可用VIP切换脚本
 * https://github.com/hcymysql/mgr_failover_vip
 *
 * 运行条件:
 * Modified by: hcymysql 2019/07/24
 * 1、MySQL 8.0版本
 * 2、单主模式
 * 3、Native Password Authentication
 * 例：> CREATE USER 'hechunyang'@'%' IDENTIFIED WITH mysql_native_password BY '123456';
 *     > GRANT ALL ON *.* TO 'hechunyang'@'%' WITH GRANT OPTION;

Usage:
  Options:
  -I  interval time seconds	设置守护进程下间隔监测时间
  --daemon 1	开启后台守护进程，0关闭后台守护进程
  --conf	指定配置文件
  --help	帮助

Example :
   前台运行
   shell> php mgr_master_ip_failover.php --conf=mgr_configure1.php

   后台运行
   shell> nohup /usr/bin/php mgr_master_ip_failover.php --conf=mgr_configure1.php -I 5 --daemon 1 > /dev/null 2>&1  &
   	   
   关闭后台运行
   shell> php mgr_master_ip_failover.php --daemon 0
\e[0m" .PHP_EOL;
}

$shortopts = "I:";

$longopts  = array(
    "conf::",
    "daemon:",
    "help",
);

$options = getopt($shortopts,$longopts);
if(empty($options) || isset($options['help'])){
	Usage();
        exit;
}

if(!isset($options['daemon'])){
	Check_MGR_Primary_Status();
	exit;
} else{
	if($options['daemon'] != 0){
		if(!isset($options['I'])){
			die("请设置间隔时间 -I 5（单位秒）"."\n");
		}
	}
	$deamon = new Daemon($options['I'],$options['conf']);
        $deamon->run($options['daemon']);
}
	
function Check_MGR_Primary_Status(){
	
global $options;

if(isset($options['conf'])){
	
require $options['conf'];

global $filename;
$filename = $options['conf'];
 
//记录监控日志       
file_put_contents(dirname(__FILE__)."/".strstr($filename,'.',true)."_master_status.health", "-----------------------------------------------------------------\n");	        

#######################################################
require_once('mgr_MFailover_class.php');

#######################################################	
require_once('mgr_Check_primary_class.php');

        //检测vip是否存在，如果不存在，退出监控程序。
        $check_vip="ssh -p $ssh_port $primary_ip -C 'ip addr list | grep $vip'";
        exec("$check_vip",$output,$return);
        if($return == 0){
                file_put_contents(dirname(__FILE__)."/".strstr($filename,'.',true)."_master_status.health", date('Y-m-d H:i:s')."\n\n"."检测到VIP: ${vip}已经存在"."\n\n", FILE_APPEND);                
		echo "检测到VIP: ${vip}已经存在".PHP_EOL.PHP_EOL;
        } else {
                file_put_contents(dirname(__FILE__)."/".strstr($filename,'.',true)."_master_status.health", date('Y-m-d H:i:s')."\n\n"."检测到VIP: ${vip}不存在"."\n\n"
."退出主程序"."\n\n", FILE_APPEND);                echo "检测到VIP: ${vip}不存在!".PHP_EOL.PHP_EOL;
                echo "退出主程序".PHP_EOL.PHP_EOL;
                exit;
        }
        //---------------------------------------------------

        $check_primary = new Check_primary($primary_ip,$new_primary_ip,$user,$passwd,$port,$new_port,$hostname,0);
        $r_status = $check_primary->check_primary();

        //检测失败切换VIP,0代表切换，1代表不切
        if ($r_status == 0){
                $check_new_primary = new Check_primary($primary_ip,$new_primary_ip,$user,$passwd,$port,$new_port,$hostname,1);
                $r_new_status = $check_new_primary->check_primary();
                if ($r_new_status == 1) {
                        $failover_primary = new MFailover($primary_ip,$new_primary_ip,$ssh_port,$vip,$network_name);
                        $failover_primary->execCommand();
                }
        }
}
}



//后台守护进程
class Daemon {
    private $pidfile;
    private $sleep_time;
    function __construct($st,$fn) {
        $this->pidfile = dirname(__FILE__).'/'.strstr($fn,'.',true).'.pid';
	$this->sleep_time = $st;
    }
 
    private function startDeamon() {
        if (file_exists($this->pidfile)) {
            echo "The file $this->pidfile exists." . PHP_EOL;
            exit();
       }
   
       $pid = pcntl_fork();
       if ($pid == -1) {
            die('could not fork\n');
       } else if ($pid) {
           echo 'start ok' . PHP_EOL;
           exit($pid);
       } else {
           file_put_contents($this->pidfile, getmypid());
           return getmypid();
        }
    }
 
    private function start(){
        $pid = $this->startDeamon();
        while (true) {
	    Check_MGR_Primary_Status();
            sleep($this->sleep_time);
        }
    }
 
    private function stop(){
        if (file_exists($this->pidfile)) {
           $pid = file_get_contents($this->pidfile);
           posix_kill($pid, 9); 
           unlink($this->pidfile);
        }
    }
 
    public function run($param) {
        if($param == 1) {
            $this->start();
        }else if($param == 0) {
            $this->stop();
	    echo "mgr_master_ip_failover.php后台守护进程已停止。". PHP_EOL;
        }
	else{
            echo 'daemon传参错误，请输入0关闭后台进程，1开启后台线程。'. PHP_EOL;
        }
    }
 
}

?>
