<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:32
 */

namespace PhpPkg\Http\Message\Response;

use InvalidArgumentException;
use PhpPkg\Http\Message\Response;
use PhpPkg\Http\Message\Stream;
use PhpPkg\Http\Message\Stream\TempStream;
use RuntimeException;

/**
 * JSON response.
 *
 * Allows creating a response by passing data to the constructor; by default,
 * serializes the data to JSON, sets a status code of 200 and sets the
 * Content-Type header to application/json.
 *
 * @package PhpPkg\Http\Message\Response
 * @from zendFramework
 */
class JsonResponse extends Response
{
    use InjectContentTypeTrait;

    /**
     * Default flags for json_encode; value of:
     *
     * <code>
     * JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
     * </code>
     *
     * @const int
     */
    public const DEFAULT_JSON_FLAGS = 79;

    /**
     * @var mixed
     */
    private $payload;

    /**
     * @var int
     */
    private $encodingOptions;


    /**
     * Create a JSON response with the given data.
     *
     * Default JSON encoding is performed with the following options, which
     * produces RFC4627-compliant JSON, capable of embedding into HTML.
     *
     * - JSON_HEX_TAG
     * - JSON_HEX_APOS
     * - JSON_HEX_AMP
     * - JSON_HEX_QUOT
     * - JSON_UNESCAPED_SLASHES
     *
     * @param mixed $data Data to convert to JSON.
     * @param int   $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @param int   $encodingOptions JSON encoding options to use.
     * @throws RuntimeException
     * @throws InvalidArgumentException if unable to encode the $data to JSON.
     */
    public function __construct(
        $data,
        int $status = 200,
        array $headers = [],
        $encodingOptions = self::DEFAULT_JSON_FLAGS
    ) {
        $this->setPayload($data);
        $this->encodingOptions = $encodingOptions;
        
        $json = $this->jsonEncode($data, $this->encodingOptions);
        $body = $this->createBodyFromJson($json);

        $headers = $this->injectContentType('application/json', $headers);

        parent::__construct($status, $headers, [], $body);
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param $data
     *
     * @return JsonResponse
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function withPayload($data): self
    {
        $new = clone $this;
        $new->setPayload($data);

        return $this->updateBodyFor($new);
    }

    /**
     * @return int
     */
    public function getEncodingOptions(): int
    {
        return $this->encodingOptions;
    }

    /**
     * @param int $encodingOptions
     *
     * @return JsonResponse
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function withEncodingOptions($encodingOptions): self
    {
        $new                  = clone $this;
        $new->encodingOptions = $encodingOptions;
        return $this->updateBodyFor($new);
    }

    /**
     * @param string $json
     *
     * @return Stream
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function createBodyFromJson($json): Stream
    {
        $body = new TempStream('wb+');
        $body->write($json);
        $body->rewind();

        return $body;
    }

    /**
     * Encode the provided data to JSON.
     *
     * @param mixed $data
     * @param int   $encodingOptions
     * @return string
     * @throws InvalidArgumentException if unable to encode the $data to JSON.
     */
    private function jsonEncode($data, int $encodingOptions): string
    {
        if (\is_resource($data)) {
            throw new InvalidArgumentException('Cannot JSON encode resources');
        }

        // Clear json_last_error()
        \json_encode(null);
        $json = \json_encode($data, $encodingOptions);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(sprintf(
                'Unable to encode data to JSON in %s: %s',
                __CLASS__,
                \json_last_error_msg()
            ));
        }

        return $json;
    }

    /**
     * @param $data
     */
    private function setPayload($data): void
    {
        if (\is_object($data)) {
            $data = clone $data;
        }

        $this->payload = $data;
    }

    /**
     * Update the response body for the given instance.
     *
     * @param self $toUpdate Instance to update.
     * @return JsonResponse Returns a new instance with an updated body.
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    private function updateBodyFor(self $toUpdate): JsonResponse
    {
        $json = $this->jsonEncode($toUpdate->payload, $toUpdate->encodingOptions);
        $body = $this->createBodyFromJson($json);

        return $toUpdate->withBody($body);
    }
}
