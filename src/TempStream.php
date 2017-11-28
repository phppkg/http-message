<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:29
 */

namespace Inhere\Http;

/**
 * Class TempStream
 * @package Inhere\Http
 */
class TempStream extends Stream
{
    /**
     * TempStream constructor.
     * @param string $mode
     * @throws \InvalidArgumentException
     */
    public function __construct($mode = 'wb+')
    {
        $stream = fopen('php://temp', $mode);

        parent::__construct($stream);
    }
}
