<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 启用redis 可以将消息写入队列 延迟写入数据库 前端发送消息也就可以不进行保存消息的请求
     * 如果需要可以自行实现
     * @param  [type] $businessWorker [description]
     * @return [type]                 [description]
     */
    public static function onWorkerStart($businessWorker)
    {
        //redis不超时
        ini_set('default_socket_timeout', -1);

        global $redis;
        $redis = new \Redis();
        $redis->pconnect('127.0.0.1', 6379);

        if (!empty($redis)) {
            echo "redis is connect!";
        }
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据 
        // Gateway::sendToClient($client_id, "Hello $client_id\r\n");
        // 向所有人发送
        // Gateway::sendToAll("$client_id login\r\n");
        
        // 绑定之前 可以进行用户的鉴权等操作
        // 像客户端发送绑定请求
        $bindArray = [
            'type' => 'bind',
            'client_id' => $client_id
        ];
        Gateway::sendToClient($client_id, json_encode($bindArray));
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        // 向所有人发送 
        // Gateway::sendToAll("$client_id said $message\r\n");

        $data = json_decode($message, true);

        if (empty($data)) {
            return;
        }

        switch ($data['type']) {
            case 'bind':
                Gateway::bindUid($client_id, $data['bind_id']);
                break;

            case 'text':
                // 可以写正则保存，qq截图，然后返回地址给消息接收方
                $data = [
                    'cid' => $client_id,
                    'type' => $data['type'],
                    'data' => $data['data'],
                    'from_id' => $data['from_id'],
                    'to_id' => $data['to_id'],
                    'msg_id' => $data['msg_id']
                ];
                // 发送给to uid
                Gateway::sendToUid($data['to_id'], json_encode($data));

                // 一端发送 多端展示发送的信息
                Gateway::sendToUid($data['from_id'], json_encode($data));

                // Gateway::sendToAll(json_encode($data));
                break;
            case 'ping':
                Gateway::sendToUid($data['from_id'], json_encode(['type' => 'ping']));
                break;

            default:
                # code...
                break;
        }

        return false;
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        // 向所有人发送 
        // GateWay::sendToAll("$client_id logout\r\n");
    }
}
