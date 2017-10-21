<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:29
 */

namespace Inhere\Http;

/**
 * Class InputStream
 * @package Inhere\Http
 */
class InputStream extends Stream
{
    /**
     * InputStream constructor.
     * @param string $mode
     */
    public function __construct($mode = 'rb+')
    {
        $stream = fopen('php://input', $mode);

        parent::__construct($stream);
    }
}
