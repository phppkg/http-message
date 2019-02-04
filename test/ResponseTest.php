<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:05
 */

namespace PhpComp\Http\MessageTest;

use PhpComp\Http\Message\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ResponseTest
 * @package PhpComp\Http\MessageTest
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
