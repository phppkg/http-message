<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-28
 * Time: 14:31
 */

namespace PhpPkg\Http\Message\Request;

use PhpPkg\Http\Message\Stream;
use function fopen;
use function rewind;
use function stream_copy_to_stream;

/**
 * Class RequestBody
 *   Provides a PSR-7 implementation of a reusable raw request body
 * @package PhpPkg\Http\Message\Request
 */
class RequestBody extends Stream
{
    /**
     * Create a new RequestBody.
     *
     * @param string|null $content
     */
    public function __construct(string $content = null)
    {
        $stream = fopen('php://temp', 'wb+');
        stream_copy_to_stream(fopen('php://input', 'rb'), $stream);
        rewind($stream);

        parent::__construct($stream);

        if ($content) {
            $this->write($content);
        }
    }
}
