<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/11/29
 * Time: 上午12:04
 */

namespace PhpPkg\Http\MessageTest;

use PhpPkg\Http\Message\Body;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * Class BodyTest
 * @package PhpPkg\Http\MessageTest
 */
class BodyTest extends TestCase
{
    public function testBody(): void
    {
        $body = new Body();

        $this->assertInstanceOf(StreamInterface::class, $body);
    }
}
