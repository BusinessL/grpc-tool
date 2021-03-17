<?php

namespace Gtool\exception;

/**
 * Class GrpcException
 *
 * @package Grpc\exception
 */
class GrpcException extends \Exception
{
    const ERROR_CODE = 500;

    const PARAMS_ERR_MSG = '解析错误';

    /**
     * GrpcException constructor.
     *
     * @param $status
     */
    public function __construct($status)
    {
        parent::__construct($status['details'], $status['code']);
    }
}