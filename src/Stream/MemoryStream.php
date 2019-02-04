<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:25
 */

namespace PhpComp\Http\Message\Component;

use PhpComp\Http\Message\Stream;

/**
 * Class MemoryStream
 * @package PhpComp\Http\Message\Component
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
