<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/11/29
 * Time: 上午12:04
 */

namespace PhpComp\Http\Message\Test;

use PhpComp\Http\Message\Body;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 * Class BodyTest
 * @package PhpComp\Http\Message\Test
 */
class BodyTest extends TestCase
{
    public function testBody()
    {
        $body = new Body();

        $this->assertInstanceOf(StreamInterface::class, $body);
    }
}
