<?php

namespace Djdj\Base\rpc;

use Djdj\Base\exception\AuthException;
use Djdj\Base\exception\BizException;
use Djdj\Base\exception\RpcException;
use Swoole\Client;

class RpcClient
{
    private $service;

    public function __call($name, $arguments)
    {
        //未设置调用服务
        if (!$this->service) {
            throw new RpcException("client not set service");
        }
        //获取要调用的微服务地址
        $consul_service = new ConsulService();
        $server_list = json_decode($consul_service->getService($this->service), true);
        if (empty($server_list)) {
            throw new RpcException($this->service . ' service not available.');
        }
        //随机负载均衡
        $target_idx = random_int(0, sizeof($server_list) - 1);
        $target_service = $server_list[$target_idx]['Service'];
        //创建client
        $client = new Client(SWOOLE_SOCK_TCP);
        $client->set(array(
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_length_offset' => 0, //第N个字节是包长度的值
            'package_body_offset' => 4, //第几个字节开始计算长度
            'package_max_length' => 2000000, //协议最大长度
        ));
        //请求参数
        $data = json_encode([
            'uid' => mt_rand(1000, 9696),
            'client' => config('consul.client.name'),
            'service' => $this->service,      //服务
            'action' => $name,               //方法
            'param' => $arguments ?? [],       //参数
        ], JSON_UNESCAPED_UNICODE);
        try {
            //连接服务方
            if (!$client->connect($target_service['Address'], $target_service['Port'], 15)) {
                throw new RpcException('Remote call connect failed. Error: ' . $client->errCode);
            }
            //发送请求
            $client->send(pack('N', strlen($data)) . $data);
            //接收服务方发回的信息
            $data = $client->recv();
            $data = json_decode(substr($data, -unpack('N', $data)[1]));
            if ($data->code == 0) {
                throw new BizException($data->msg);
            } elseif ($data->code == 401) {
                throw new AuthException($data->data);
            } elseif ($data->code == 500) {
                throw new RpcException('call ' . $this->service . ' error: ' . $data->msg);
            }
            return $data->data;
        } finally {
            $client->close();
        }
    }

    public static function connect($server_name)
    {
        $client = new self();
        $client->service = $server_name;
        return $client;
    }
}