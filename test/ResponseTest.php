<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:05
 */

namespace PhpPkg\Http\MessageTest;

use PhpPkg\Http\Message\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ResponseTest
 * @package PhpPkg\Http\MessageTest
 */
class ResponseTest extends TestCase
{
    public function testClass(): void
    {
        $res = new Response();

        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertIsString($res->getReasonPhrase());
    }
}
