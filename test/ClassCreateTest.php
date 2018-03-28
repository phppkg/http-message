<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:43
 */

namespace Inhere\Http\Test;

use Inhere\Http\Body;
use Inhere\Http\Request;
use Inhere\Http\Response;
use Inhere\Http\ServerRequest;
use Inhere\Http\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{
    ServerRequestInterface, StreamInterface, ResponseInterface, RequestInterface, UriInterface
};

/**
 * Class ClassCreateTest
 * @package Inhere\Http\Test
 */
class ClassCreateTest extends TestCase
{
    public function testCreateClass()
    {
        $obj = new Uri();
        $this->assertInstanceOf(UriInterface::class, $obj);

        $obj = new Body();
        $this->assertInstanceOf(StreamInterface::class, $obj);

        $obj = new Request();
        $this->assertInstanceOf(RequestInterface::class, $obj);

        $obj = new ServerRequest();
        $this->assertInstanceOf(ServerRequestInterface::class, $obj);

        $obj = new Response();
        $this->assertInstanceOf(ResponseInterface::class, $obj);
    }
}
