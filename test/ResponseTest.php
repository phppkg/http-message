<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:05
 */

namespace Inhere\Http\Test;

use Inhere\Http\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ResponseTest
 * @package Inhere\Http\Test
 */
class ResponseTest extends TestCase
{
    public function testClass()
    {
        $res = new Response();

        $this->assertInstanceOf(ResponseInterface::class, $res);
        $this->assertSame(200, $res->getStatusCode());
        $this->assertInternalType('string', $res->getReasonPhrase());
    }
}
