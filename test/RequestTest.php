<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:05
 */

namespace PhpPkg\Http\MessageTest;

use PhpPkg\Http\Message\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Class RequestTest
 * @package PhpPkg\Http\MessageTest
 */
class RequestTest extends TestCase
{
    public function testClass(): void
    {
        $req = new Request();

        $this->assertInstanceOf(RequestInterface::class, $req);
    }
}
