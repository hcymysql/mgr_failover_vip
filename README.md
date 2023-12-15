# mgr_failover_vip
MySQL 8.0 MGR（组复制）高可用VIP故障转移脚本

视频演示：https://www.bilibili.com/video/BV18a4y197TG/

简介：MGR（组复制）官方推荐用MySQL router中间件去做MGR高可用故障转移，但其多过了一层网络，性能会下降，并且需要额外维护一套中间件，运维成本过高，于是写了一个类似MHA的master_ip_failover脚本，实现VIP切换。

1）脚本会自动设置当前Primary和备选Primary参数group_replication_member_weight值为100（权重100，默认为50的Secondary不进行vip切换）

2）脚本会自动设置当前Primary和备选Primary参数group_replication_consistency值为BEFORE_ON_PRIMARY_FAILOVER（意思为当Primary挂了的时候，备选Primary只有把事务全部执行完毕，才提供客户端读写操作）

3）最好生产关闭限流模式set global group_replication_flow_control_mode = 'DISABLED'，以防止高并发期间自动触发限流，造成主库不可写，引起生产事故。


 * 环境准备:
 
 1) ```shell> yum install -y php-process php php-mysql```
 2) 开通监控管理机和MGR SSH互信（可用SSH主机批量互信认证脚本https://github.com/hcymysql/batch_sshkey）

 3) 手工添加VIP地址
```shell> ip addr add 192.168.148.100/32 dev eth0 ; arping -q -c 2 -U -I eth0 192.168.148.100```
 
 * 运行条件:
 * 1、MySQL 8.0版本
 * 2、single-primary mode（单主模式）
 * 3、Native Password Authentication（5.5/5.6/5.7传统用户认证模式）
 * 例：
      > CREATE USER 'hechunyang'@'%' IDENTIFIED WITH mysql_native_password BY '123456';
      
      > GRANT ALL ON *.* TO 'hechunyang'@'%' WITH GRANT OPTION;

Usage:

  Options:
  
  -I  interval time seconds	设置守护进程下间隔监测时间
  
  --daemon 1	开启后台守护进程，0关闭后台守护进程
  
  --conf	指定配置文件
  
  --help	帮助

Example :

   前台运行
   
   ```shell> php mgr_master_ip_failover.php --conf=mgr_configure1.php```

   后台运行
   
   ```shell> nohup /usr/bin/php mgr_master_ip_failover.php --conf=mgr_configure1.php -I 5 --daemon 1 > /dev/null 2>&1  &```
   	   
   关闭后台运行
   
   ```shell> php mgr_master_ip_failover.php --conf=mgr_configure1.php --daemon 0```
   
   
mgr_configure1.php为配置文件，你可以配置多个监控配置文件，监控多套MGR环境。


