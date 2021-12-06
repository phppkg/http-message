<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:29
 */

namespace PhpPkg\Http\Message\Stream;

use InvalidArgumentException;
use PhpPkg\Http\Message\Stream;
use function fopen;

/**
 * Class InputStream
 * @package PhpPkg\Http\Message\Stream
 */
class InputStream extends Stream
{
    /**
     * InputStream constructor.
     * @param string $mode
     * @throws InvalidArgumentException
     */
    public function __construct(string $mode = 'rb+')
    {
        $stream = fopen('php://input', $mode);

        parent::__construct($stream);
    }
}
