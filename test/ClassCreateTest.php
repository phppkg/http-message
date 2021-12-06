<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:43
 */

namespace PhpPkg\Http\MessageTest;

use PhpPkg\Http\Message\Body;
use PhpPkg\Http\Message\Request;
use PhpPkg\Http\Message\Response;
use PhpPkg\Http\Message\ServerRequest;
use PhpPkg\Http\Message\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{
    ServerRequestInterface, StreamInterface, ResponseInterface, RequestInterface, UriInterface
};

/**
 * Class ClassCreateTest
 * @package PhpPkg\Http\MessageTest
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
