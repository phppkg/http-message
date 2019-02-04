<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:05
 */

namespace PhpComp\Http\MessageTest;

use PhpComp\Http\Message\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Class RequestTest
 * @package PhpComp\Http\MessageTest
 */
class RequestTest extends TestCase
{
    public function testClass(): void
    {
        $req = new Request();

        $this->assertInstanceOf(RequestInterface::class, $req);
    }
}
