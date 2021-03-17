<?php

namespace Gtool;

use Gtool\lib\Util;
use Gtool\exception\GrpcException;

/**
 * Class Service
 *
 * @package Grpc
 */
class Service
{
    use Util;

    const STATUS_OK = 0;
    const STATUS_ERROR = -1;

    // 服务端地址
    protected $hostname = '';
    // pb文件命名空间前缀
    protected $namespacePrefix = "";
    // 调用的服务名
    protected $serviceName = "";
    // 调用的方法名
    protected $actionName = "";
    // 发起请求的客户端
    protected $clients = [];
    // 请求读写
    protected $calls = [];

    /**
     * Service constructor.
     *
     * @param $hostname
     * @param $namespacePrefix
     */
    public function __construct($hostname, $namespacePrefix)
    {
        $this->hostname = $hostname;
        $this->namespacePrefix = $namespacePrefix;
    }

    /**
     * 单向流grpc
     *
     * @param $serviceName
     * @param $actionName
     * @param $request
     * @return mixed
     * @throws GrpcException
     * @throws \ReflectionException
     */
    public function unaryCall($serviceName, $actionName, $request)
    {
        $this->setAttr($serviceName, $actionName);
        $client = $this->getClient();

        $call = $client->$actionName($request);
        list($reply, $status) = $call->wait();

        $this->checkStatus($status);

        $resArr = $this->parseToArray($reply);

        return $resArr;
    }


    /**
     * 双向流grpc
     *
     * @param $serviceName
     * @param $actionName
     * @param $request
     * @return mixed
     * @throws GrpcException
     * @throws \ReflectionException
     */
    public function bidirectionalGrpc($serviceName, $actionName, $request)
    {
        $this->setAttr($serviceName, $actionName);
        $call = $this->getCall();

        try {
            $call->write($request);
        } catch (\Exception $e) {
            $this->removeCall();
            // 重试
            $call = $this->getCall();
            $call->write($request);
        }

        $reply = $call->read();

        $this->checkStatus($call->getStatus());

        $resArr = $this->parseToArray($reply);

        return $resArr;
    }
}