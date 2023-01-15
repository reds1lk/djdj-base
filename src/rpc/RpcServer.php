<?php

namespace Djdj\Base\rpc;

use Djdj\Base\common\JsonResponse;
use Djdj\Base\constant\AuthConstant;
use Djdj\Base\exception\AuthException;
use Djdj\Base\exception\BizException;
use Djdj\Base\exception\RpcException;
use Djdj\Base\model\RpcLog;
use Exception;
use ReflectionMethod;
use Swoole\Server;
use think\exception\ValidateException;
use think\Log;
use Throwable;

class RpcServer
{
    protected $server;
    protected $ip;
    protected $port;
    protected $name;

    public function __construct()
    {
        $this->id = config('consul.client.id');
        $this->name = config('consul.client.name');
        $this->tags = config('consul.client.tags');
        $this->ip = config('consul.client.ip');
        $this->port = (int)config('consul.client.port');
        $this->server = new Server($this->ip, $this->port);
        $this->server->set(array(
            'open_length_check' => true,    // 开启协议解析
            'package_length_type' => 'N',     // 长度字段的类型
            'package_length_offset' => 0,       //从第几个字节是包长度的值
            'package_body_offset' => 4,       //从第几个字节开始计算长度
            'package_max_length' => 81920,   //包的最大长度
            'worker_num' => 4,
            'max_request' => 10000,
            'max_conn' => 10000,
            'dispatch_mode' => 2,
            'debug_mode' => config('app.app_debug', false),
            'daemonize' => 1,
            'tcp_keepcount' => 5,
            'tcp_keepinterval' => 60,
            'pid_file' => config('consul.client.pid_file'),
        ));
        $this->onConnect();
        $this->onReceive();
        $this->onClose();
        $this->onWorkerStart();
        $this->onStart();
        $this->server->start();
    }

    public function onConnect()
    {
        $this->server->on('Connect', function ($serv, $fd) {

        });
    }

    public function onReceive()
    {
        $this->server->on('Receive', function ($serv, $fd, $from_id, $data) {
            $startTime = microtime(true);
            //解包网络数据包
            $data = json_decode(substr($data, -unpack('N', $data)[1]), true);
            $uid = $data['uid'];
            $client = $data['client'];
            $service = $data['service'];
            $action = $data['action'];
            $params = $data['param'];
            $instance = app($service);
            $response = new JsonResponse();
            $response->setEncrypt(false); //不加密
            try {
                $method = new ReflectionMethod(get_class($instance), $action);
                if (sizeof($params) < $method->getNumberOfRequiredParameters() || sizeof($params) > $method->getNumberOfParameters()) {
                    throw new RpcException('method [ ' . $action . ' ] parameter number not match');
                }
                $response->setData($instance->$action(...$params));
            } catch (Throwable $e) {
                if ($e instanceof AuthException) {
                    $response->setCode(401);
                    $response->setMsg(AuthConstant::STATE_LIST[$e->state]);
                } elseif ($e instanceof ValidateException) {
                    $response->setCode(0);
                    if (is_array($e->getError())) {
                        $response->setMsg('参数错误');
                        $response->setData($e->getError());
                    } else {
                        $response->setMsg($e->getError());
                    }
                } elseif ($e instanceof BizException) {
                    $response->setCode(0);
                    $response->setMsg($e->getMessage());
                } else {
                    $response->setCode(500);
                    $response->setMsg($e->getMessage());
                    $response->setTrace($e->getTrace());
                    $response->setShowTrace(true);
                }
            }
            $data = $response->json()->getContent();
            //返回信息到swoole的client端
            $serv->send($fd, pack('N', strlen($data)) . $data);
            //调用日志
            if (env('rpc_log', false)) {
                RpcLog::insert([
                    'uid' => $uid,
                    'client' => $client,
                    'action' => $service . '.' . $action,
                    'params' => $params,
                    'response' => json_decode($data, true),
                    'duration' => (int)((microtime(true) - $startTime) * 1000),
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            }
        });
    }

    public function onClose()
    {
        $this->server->on('Close', function ($serv, $fd) {

        });
    }

    public function onWorkerStart()
    {
        $this->server->on('WorkerStart', function ($serv, $fd) {

        });
    }

    public function onStart()
    {
        $this->server->on('start', function ($serv) {
            $data = [
                "ID" => $this->id,
                "Name" => $this->name,
                "Tags" => [$this->tags],
                "Address" => $this->ip,
                "Port" => $this->port,
                "CheckSign" => ["TCP" => $this->ip . ':' . $this->port, "Interval" => "5s"]
            ];
            //注册服务
            $consul = new ConsulService();
            $err_msg = $consul->registerService(json_encode($data));
            if (!empty($err_msg)) {
                throw new Exception($err_msg);
            }
        });
    }
}
