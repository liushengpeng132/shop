<?php
error_reporting(E_ALL);
set_time_limit(0);
//监听端口
$PORT = 8888;
//最大连接池
$MAX_USERS = 50;
//创建监听端口
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
@socket_bind($sock,'192.168.11.250',$PORT);
@socket_listen($sock,$MAX_USERS);
if(!$sock){
    exit(1);
}
//不阻塞
socket_set_nonblock($sock);
$connections = array();
$close = array();
while(true){
    //sleep(3);
    $readfds = array_merge($connections, array($sock));
    // var_dump($readfds);
    $writefds = array();
    //选择一个连接，获取读、写连接通道
    if(socket_select($readfds,$writefds,$e = null,$t = 60)){
        foreach ($readfds as $rfd){
            //如果是当前服务端的监听连接
            if ($rfd == $sock){
                //接受客户端连接
                $newconn = socket_accept($sock);
                $i = (int)$newconn;
                $reject = '';
                var_dump($newconn);
                if (count($connections) >= $MAX_USERS){
                    $reject = "连接失败"."\n";                   
                }                
                //将当前客户端连接放如socket_select选择
                $connections[$i] = $newconn;
                //输入的连接资源缓存容器
                $writefds[$i] = $newconn;               
                //连接不正常
                if ($reject){                  
                    $close[$i] = true;
                }else{
                    echo "连接成功"."\n";                  
                }               
                continue;
            }
            //客户端连接
            $i = (int)$rfd;
            //读取
            $tmp = @socket_read($rfd,8096);
            if(!$tmp){
                //读取不到内容              
                echo "读取内容失败!"."\n";
                close($i);
                continue;
            }
            $line = trim($tmp);
            echo 'Client >>'.$line."\r\n";                            
            socket_getpeername($connections[$i],$remoteIP,$remotePort);
      echo $remoteIP."\r\n";
      echo $remotePort."\r\n";
            foreach ($connections as $wfd){
                $strs = $line."\0";
                socket_send($wfd,$strs,strlen($strs),0);
            }   
        }
        
    }   
}

function close($i)
{
    global $connections, $input, $close;
    socket_shutdown($connections[$i]);
    socket_close($connections[$i]);
    unset($connections[$i]); 
    unset($close[$i]);
}
?>


