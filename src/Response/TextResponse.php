<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:24
 */

namespace Inhere\Http\Response;

use Inhere\Http\Response;
use Inhere\Http\TempStream;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

/**
 * Class TextResponse - Plain text response.
 *
 * Allows creating a response by passing a string to the constructor;
 * by default, sets a status code of 200 and sets the Content-Type header to
 * text/plain.
 *
 * @package Inhere\Http\Response
 * @from zendFramework
 */
class TextResponse extends Response
{
    use InjectContentTypeTrait;

    /**
     * Create a plain text response.
     *
     * Produces a text response with a Content-Type of text/plain and a default
     * status of 200.
     *
     * @param string|StreamInterface $text String or stream for the message body.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @throws \RuntimeException
     * @throws InvalidArgumentException if $text is neither a string or stream.
     */
    public function __construct($text, $status = 200, array $headers = [])
    {
        parent::__construct(
            $this->createBody($text),
            $status,
            $this->injectContentType('text/plain; charset=utf-8', $headers)
        );
    }

    /**
     * Create the message body.
     *
     * @param string|StreamInterface $text
     * @return StreamInterface
     * @throws \RuntimeException
     * @throws InvalidArgumentException if $html is neither a string or stream.
     */
    private function createBody($text)
    {
        if ($text instanceof StreamInterface) {
            return $text;
        }

        if (! \is_string($text)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid content (%s) provided to %s',
                (\is_object($text) ? \get_class($text) : \gettype($text)),
                __CLASS__
            ));
        }

        $body = new TempStream('wb+');
        $body->write($text);
        $body->rewind();

        return $body;
    }
}
