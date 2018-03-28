<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:05
 */

namespace Inhere\Http\Test;

use Inhere\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Class RequestTest
 * @package Inhere\Http\Test
 */
class RequestTest extends TestCase
{
    public function testClass()
    {
        $req = new Request();

        $this->assertInstanceOf(RequestInterface::class, $req);
    }
}
