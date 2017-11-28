<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/11/29
 * Time: 上午12:04
 */

namespace Inhere\Http\Tests;

use Inhere\Http\Body;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * Class BodyTest
 * @package Inhere\Http\Tests
 */
class BodyTest extends TestCase
{
    public function testBody()
    {
        $body = new Body();

        $this->assertInstanceOf(StreamInterface::class, $body);
    }
}
