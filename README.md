# php http message

some useful http message library(psr7 implement) of the php

## project

- **github** https://github.com/inhere/php-http.git
- **git@osc** https://git.oschina.net/inhere/php-http.git

## install

- by composer

edit `composer.json`，at `require` add

```
"inhere/http": "dev-master",
```

run: `composer update`

- Direct fetch

```
git clone https://git.oschina.net/inhere/php-http.git // git@osc
git clone https://github.com/inhere/php-http.git // github
```

## usage

### basic

```php
use Inhere\Http\Request;
use Inhere\Http\Response;

$request = new Request($method, $uri);
$request = new ServerRequest(... ...);
$response = new Response($code);
... ...
```

### use factory

```php
use Inhere\Http\HttpFactory;

$request = HttpFactory::createRequest($method, $uri);

// server request
$request = HttpFactory::createServerRequest('GET', 'http://www.abc.com/home');
$request = HttpFactory::createServerRequestFromArray($_SERVER);

$response = HttpFactory::createResponse($code);
```

### Extended

```php
use Inhere\Http\Request;
use Inhere\Http\Extra\ExtendedRequestTrait;

class MyRequest extends Request {
   use ExtendedRequestTrait;
}

// 

$request = new MyRequest(...);

$age = $request->getInt('age');
$name = $request->getTrimmed('name');

```

```php
use Inhere\Http\Response;
use Inhere\Http\Extra\ExtendedResponseTrait;

class MyResponse extends Response {
   use ExtendedResponseTrait;
}
```

## license

MIT
