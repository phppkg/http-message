<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:05
 */

namespace PhpComp\Http\Message\Test;

use PhpComp\Http\Message\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Class RequestTest
 * @package PhpComp\Http\Message\Test
 */
class ServerRequestTest extends TestCase
{
    public function testClass()
    {
        $req = new ServerRequest();

        $this->assertInstanceOf(RequestInterface::class, $req);

        $req = $req->withAttribute('key', 'value');

        $this->assertInstanceOf(RequestInterface::class, $req);
        $this->assertSame('value', $req->getAttribute('key'));
    }
}
