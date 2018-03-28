<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/3/28 0028
 * Time: 22:25
 */

namespace Inhere\Http\Component;

use Inhere\Http\Stream;

/**
 * Class MemoryStream
 * @package Inhere\Http\Component
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
