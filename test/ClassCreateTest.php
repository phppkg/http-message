<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:43
 */

namespace PhpComp\Http\MessageTest;

use PhpComp\Http\Message\Body;
use PhpComp\Http\Message\Request;
use PhpComp\Http\Message\Response;
use PhpComp\Http\Message\ServerRequest;
use PhpComp\Http\Message\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{
    ServerRequestInterface, StreamInterface, ResponseInterface, RequestInterface, UriInterface
};

/**
 * Class ClassCreateTest
 * @package PhpComp\Http\MessageTest
 */
class ClassCreateTest extends TestCase
{
    public function testCreateClass(): void
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
