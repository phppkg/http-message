<?php declare(strict_types=1);

namespace PhpPkg\Http\Message\Util;

use Psr\Http\Message\ResponseInterface;
use function array_merge;
use function connection_status;
use function header;
use function headers_sent;
use function in_array;
use function method_exists;
use function min;
use function sprintf;
use function strlen;
use const CONNECTION_NORMAL;

/**
 * class HttpUtil
 *
 * @author inhere
 */
class HttpUtil
{
    public const FAV_ICON = '/favicon.ico';

    /**
     * Send the response the client
     * @param ResponseInterface|Response $response
     * @param array             $options
     */
    public static function respond(ResponseInterface $response, array $options = []): void
    {
        $options = array_merge([
            'chunkSize'              => 4096,
            'addContentLengthHeader' => false,
        ], $options);

        // Send response
        if (!headers_sent()) {
            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                /** @var array $values */
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        // Body
        if (!self::isEmptyResponse($response)) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }

            $chunkSize     = $options['chunkSize'];
            $contentLength = $response->getHeaderLine('Content-Length');

            if (!$contentLength) {
                $contentLength = $body->getSize();
            }

            if (null !== $contentLength) {
                $amountToRead = $contentLength;

                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;
                    $amountToRead -= strlen($data);

                    if (connection_status() !== CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);

                    if (connection_status() !== CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Helper method, which returns true if the provided response must not output a body and false
     * if the response could have a body.
     * @see https://tools.ietf.org/html/rfc7231
     * @param ResponseInterface $response
     * @return bool
     */
    public static function isEmptyResponse(ResponseInterface $response): bool
    {
        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }

        return in_array($response->getStatusCode(), [204, 205, 304], true);
    }
}
