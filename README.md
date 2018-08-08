## 高性能swoole

###安装swoole

1 异步Redis客户端的支持，基于redis官方提供的hiredis库实现，[参考链接](https://wiki.swoole.com/wiki/page/p-redis.html)
```
tar -xf hiredis-0.13.3.tar.gz
cd hiredis-0.13.3
make -j
sudo make install
sudo ldconfig
```
2 [编译安装swoole](https://wiki.swoole.com/wiki/page/6.html)，[支持协程](https://wiki.swoole.com/wiki/page/749.html)、[异步mysql 客户端](https://wiki.swoole.com/wiki/page/517.html)
```
tar -xf swoole-src-2.0.10-rc2.tar.gz
cd swoole-src-2.0.10-rc2
/opt/app/php7/bin/phpize
./configure --with-php-config=/opt/app/php7/bin/php-config --enable-async-redis --enable-coroutine
```

Swoole-2.x [安装要求](https://wiki.swoole.com/wiki/page/7.html)：
1. 仅支持 Linux、FreeBSD、MacOS 三种操作系统
2. gcc4.4 以上版本(gcc -v 查看自己版本)
3. Linux 内核版本 2.3.32 以上(uname -r 查看内核版本)


性能测试:
- swoole、nginx、Golang  web 性能比较**100 个并发，100万http请求基准测试**
- swoole、swoole （mysql 线程池）、nginx + fastCGI + php  QPS性能比较
```bash
#swoole、nginx、Golang  web 性能比较
# nginx helloworld 测试
ab -c 100 -n 1000000 -k http://127.0.0.1:9508/
# golang hello，world 测试
ab -c 100 -n 1000000 -k http://127.0.0.1:8080/
# swoole helloworld 测试
php http-server.php > /dev/null 2>&1
ab -c 100 -n 1000000 -k http://127.0.0.1:9505/

#swoole、swoole （mysql 线程池）、nginx + fastCGI + php

#swoole 同步mysql
php swoole-mysql.php > /dev/null 2>&1
ab -c 2000 -n 20000 http://192.168.132.128:9502/

#swoole mysql 线程池
php swoole-mysql-pool.php > /dev/null 2>&1
ab -c 2000 -n 20000 http://192.168.132.128:9501/
#nginx + fastCGI
ab -c 2000 -n 20000 http://192.168.132.128:9508/pool/sync-mysql.php
```


swoole web 并发 远远大于 nginx + fastCGI 原因分析：

关键在于同步阻塞与异步非阻塞模型

**异步的优势**
- 高并发，同步阻塞IO模型的并发能力依赖于进程/线程数量，例如 php-fpm开启了200个进程，理论上最大支持的并发能力为200。如果每个请求平均需要100ms，那么应用程序就可以提供2000qps。异步非阻塞的并发能力几乎是无限的，可以发起或维持大量并发TCP连接
无IO等待，同步模型无法解决IOWait很高的场景，如每个请求平均要10s，那么应用程序就只能提供20qps了。而异步程序不存在IO等待，所以无论请求要花费多长时间，对整个程序的处理能力没有任何影响
- Swoole使用epoll作为事件轮询，可维持大量TCP连接。只要操作系统的内存足够，就一直可以增加维持的TCP长连接。[swoole_server每个连接所占用的内存为220字节](https://wiki.swoole.com/wiki/page/p-c100k.html),fast-CGI 一个worker 进程大约2.7M内存

**同步的优势**

- 编码简单，同步模式编写/调试程序更轻松，可控性好，同步模式的程序具有良好的过载保护机制，如在下面的情况异步程序就会出问题
Accept保护，同步模式下一个TCP服务器最大能接受 进程数+Backlog 个TCP连接。一旦超过此数量，Server将无法再接受连接，客户端会连接失败。避免服务器Accept太多连接，导致请求堆积


采坑总结：
1. [协程拓展冲突](https://wiki.swoole.com/wiki/page/851.html)，建议swoole 使用自己php 配置文件
2. 框架中使用swoole，PDO 超时关闭，致使数据库操作失败，[建议封装mysql 连接池](https://github.com/buyingfei/swoole-share/blob/master/pool/swoole-mysql-pool.php)，封装自己数据库操作，保证断线mysql重连 
3. [禁止使用exit/die](https://wiki.swoole.com/wiki/page/501.html),建议使用try-catch 捕捉异常


