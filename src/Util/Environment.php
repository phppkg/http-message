<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2015/2/27
 * Use : ...
 */

namespace PhpPkg\Http\Message\Util;

use function array_merge;
use function microtime;
use function time;

/**
 * mock 环境信息
 * Class Environment
 *
 * @package PhpPkg\Http\Message\Util
 */
class Environment extends Collection
{
    /**
     * Create mock environment
     * @param  array $userData Array of custom environment keys and values
     * @return self
     */
    public static function mock(array $userData = []): self
    {
        $data = array_merge([
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => 'GET',
            'SCRIPT_NAME'          => '',
            'REQUEST_URI'          => '',
            'QUERY_STRING'         => '',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => 80,
            'HTTP_HOST'            => 'localhost',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET'  => 'utf-8;q=0.7,*;q=0.3',
            'HTTP_USER_AGENT'      => 'MY Framework',
            'REMOTE_ADDR'          => '127.0.0.1',
            'REQUEST_TIME'         => time(),
            'REQUEST_TIME_FLOAT'   => microtime(true),
        ], $userData);

        return new static($data);
    }
}
