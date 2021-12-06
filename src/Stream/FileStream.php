<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:29
 */

namespace PhpPkg\Http\Message\Stream;

use PhpPkg\Http\Message\Stream;
use function fopen;

/**
 * Class FileStream
 * @package PhpPkg\Http\Message\Stream
 */
class FileStream extends Stream
{
    /**
     * Class constructor.
     * @param string $file
     * @param string $mode
     */
    public function __construct(string $file, string $mode = 'rb+')
    {
        $stream = fopen($file, $mode);

        parent::__construct($stream);
    }
}
