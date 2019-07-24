# mgr_failover_vip
MySQL 8.0 MGR（组复制）高可用VIP切换脚本

 * MGR（组复制）高可用VIP切换脚本
 * https://github.com/hcymysql/mgr_failover_vip
 *
 * 运行条件:
 * Modified by: hcymysql 2019/07/24
 * 1、MySQL 8.0版本
 * 2、单主模式
 * 3、Native Password Authentication
 * 例：> CREATE USER 'hechunyang'@'%' IDENTIFIED WITH mysql_native_password BY '159753';
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
   
   
