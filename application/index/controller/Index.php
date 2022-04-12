<?php

namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    /**
     * 聊天页面
     * from-id 发送方用户id
     * to-id 接收方用户id
     * @return [type] [description]
     */
    public function index()
    {
        $fromId = input("from-id");
        $toId = input("to-id");

        $this->assign('fromId', $fromId);
        $this->assign('toId', $toId);
        
        return $this->fetch();
    }

    /**
     * 保存聊天数据 返回消息id
     * @return [type] [description]
     */
    public function saveMsg()
    {
        // 这里用redis模拟的保存消息
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);

        $cacheId = 'msg:incr:id:for:test';

        $msgId = $redis->incr($cacheId);

        $jsonArray = [
            'code' => 200,
            'msg' => '亲，消息发送成功',
            'data' => [
                'id' => $msgId
            ]
        ];
        return json($jsonArray);
    }
}
