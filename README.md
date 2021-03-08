# grpc-tool

介绍

该composer包，封装grpc传输方式，提供更简单的调用方法。

安装
```
composer require yun-hai/grpc-tool
```

使用

> 单向流

```
$service = new Service($hostnames, $namespacePrefix);

$result = $service->unaryCall($serviceName, $actionName, $request);

```

> 双向流

```
$service = new Service($hostnames, $namespacePrefix);

$result = $grpc->bidirectionalGrpc($serviceName, $actionName, $request);

```