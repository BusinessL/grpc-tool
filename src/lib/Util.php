<?php

namespace Grpc\lib;

use Google\Protobuf\Internal\Message;
use Google\Protobuf\Internal\RepeatedField;
use Grpc\exception\GrpcException;

/**
 * Trait Util
 *
 * @package Grpc\lib
 */
trait Util
{
    /**
     * 设置初始变量
     *
     * @param string $serviceName
     */
    protected function setAttr($serviceName)
    {
        $this->serviceName = $serviceName;
    }

    /**
     * 获取客户端
     *
     * @param \Grpc\Channel $channel (optional) re-use channel object
     * @return mixed
     */
    protected function getClient($channel = null)
    {
        $service = '\\' . $this->namespacePrefix . '\\' . $this->serviceName;

        if (empty($this->clients[$service])) {
            $this->clients[$service] = new $service($this->hostname,
                [
                    'credentials' => \Grpc\ChannelCredentials::createInsecure(),
                    'timeout' => 1000000
                ],
                $channel);
        }

        return $this->clients[$service];
    }

    /**
     * 关闭客户端【移除】
     */
    public function removeClient()
    {
        $service = '\\' . $this->namespacePrefix . '\\' . $this->serviceName;

        $client = $this->clients[$service];
        $client->close();
        $this->clients[$service] = null;
    }

    /**
     * 检查返回值
     *
     * @param $status
     * @throws GrpcException
     */
    protected function checkStatus($status)
    {
        if ($status->code != self::STATUS_OK) {
            $this->removeClient();
            throw new GrpcException(get_object_vars($status));
        }
    }

    /**
     * 格式化返回值
     *
     * @param $data
     * @return mixed
     * @throws GrpcException
     * @throws \ReflectionException
     */
    protected function parseToArray($data)
    {
        $reflects = new \ReflectionClass($data);
        $methods = $reflects->getMethods(\ReflectionMethod::IS_PUBLIC);

        $arr = [];
        foreach ($methods as $method) {
            $funcName = $method->getName();

            if (substr($funcName, 0, 3) == 'get') {
                //取key
                $propKey = substr($funcName, 3);
                //取值
                $ref = $data->$funcName();

                //不是对象,直接返回
                if (!is_object($ref)) {
                    $arr[$propKey] = $ref;
                } else if ($ref instanceof RepeatedField) {
                    $arr[$propKey] = [];
                    foreach ($ref as $key => $items) {
                        if (is_object($items)) {
                            $arr[$propKey][] = $this->parseToArray($items);
                        } else {
                            $arr[$propKey][] = $items;
                        }
                    }
                } else if ($ref instanceof Message) {
                    $arr[$propKey] = $this->parseToArray($ref);
                } else {
                    $throwMsg = [
                        'code' => GrpcException::ERROR_CODE,
                        'details' => GrpcException::PARAMS_ERR_MSG
                    ];

                    throw new GrpcException($throwMsg);
                }
            }
        }

        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $arr));
    }
}