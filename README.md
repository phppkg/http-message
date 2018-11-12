# http message

http message 库，实现自 PSR 7。

## 安装

- 通过 `composer.json`

编辑 `composer.json`，在 `require` 添加

```
"php-comp/http-message": "dev-master",
```

保存，然后执行: `composer update`

- 通过 `composer require`

```bash
composer require php-comp/http-message
```

- git拉取

```bash
git clone https://gitee.com/inhere/php-http.git // git@osc
git clone https://github.com/php-comp/http-message.git // github
```

## 使用

### 基本使用

```php
use PhpComp\Http\Request;
use PhpComp\Http\Response;

$request = new Request($method, $uri);
$request = new ServerRequest(... ...);
$response = new Response($code);
... ...
```

### 工厂方法

使用提供的工厂方法可以快速创建想要的实例对象。

```php
use PhpComp\Http\HttpFactory;

$request = HttpFactory::createRequest($method, $uri);

// server request
$request = HttpFactory::createServerRequest('GET', 'http://www.abc.com/home');
$request = HttpFactory::createServerRequestFromArray($_SERVER);

$response = HttpFactory::createResponse($code);
```

### 扩展

```php
use PhpComp\Http\Request;
use PhpComp\Http\Extra\ExtendedRequestTrait;

class MyRequest extends Request {
   use ExtendedRequestTrait; // 里面提供的更多方便使用的方法
}

// 

$request = new MyRequest(...);

$age = $request->getInt('age');
$name = $request->getTrimmed('name');

```

```php
use PhpComp\Http\Response;
use PhpComp\Http\Extra\ExtendedResponseTrait;

class MyResponse extends Response {
   use ExtendedResponseTrait;
}
```

## 项目地址

- **github** https://github.com/php-comp/http-message
- **git@osc** https://gitee.com/php-comp/http-message

## License

[MIT](LICENSE)
