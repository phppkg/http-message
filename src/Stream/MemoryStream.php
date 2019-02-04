<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:25
 */

namespace PhpComp\Http\Message\Stream;

use PhpComp\Http\Message\Stream;

/**
 * Class MemoryStream
 * @package PhpComp\Http\Message\Stream
 */
class MemoryStream extends Stream
{
    /**
     * class constructor.
     * @param string $mode
     * @throws \InvalidArgumentException
     */
    public function __construct(string $mode = 'b')
    {
        $stream = \fopen('php://memory', $mode);

        parent::__construct($stream);
    }
}
