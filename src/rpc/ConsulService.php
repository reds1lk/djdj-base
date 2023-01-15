<?php

namespace Djdj\Base\rpc;


use think\facade\Config;

class ConsulService
{
    //注意，此处的IP和端口只能是consul_client服务的IP和端口
    public $server_ip;
    public $server_port;

    public function __construct()
    {
        $this->server_ip = Config::get('consul.server.ip');
        $this->server_port = (int)Config::get('consul.server.port');
    }

    /**
     * 服务注册
     */
    public function registerService($json)
    {
        return $this->curlPUT("/v1/agent/service/register", $json);
    }

    /**
     * 销毁服务
     */
    public function deregisterService($service_id)
    {
        return $this->curlPUT("/v1/agent/service/deregister/$service_id", null);
    }

    /**
     * 获取consul的服务信息
     */
    public function getService($service_id)
    {
        return $this->curlGET("/v1/health/service/$service_id" . '?passing=true', null);
    }

    /**
     * PUT请求
     */
    public function curlPUT($request_uri, $data)
    {

        $ch = curl_init();
        $header[] = "Content-type:application/json";
        $httpUrl = "http://{$this->server_ip}:{$this->server_port}{$request_uri}";
        curl_setopt($ch, CURLOPT_URL, $httpUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * GET请求
     */
    public function curlGET($request_uri, $data)
    {
        $ch = curl_init();
        $header[] = "Content-type:application/json";
        $httpUrl = "http://{$this->server_ip}:{$this->server_port}{$request_uri}";
        curl_setopt($ch, CURLOPT_URL, $httpUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}