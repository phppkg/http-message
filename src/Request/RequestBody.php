<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-28
 * Time: 14:31
 */

namespace PhpComp\Http\Message\Request;

use PhpComp\Http\Message\Stream;

/**
 * Class RequestBody
 *   Provides a PSR-7 implementation of a reusable raw request body
 * @package PhpComp\Http\Message\Request
 */
class RequestBody extends Stream
{
    /**
     * Create a new RequestBody.
     * @param string $content
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __construct(string $content = null)
    {
        $stream = \fopen('php://temp', 'wb+');
        \stream_copy_to_stream(fopen('php://input', 'rb'), $stream);
        \rewind($stream);

        parent::__construct($stream);

        if ($content) {
            $this->write($content);
        }
    }
}
