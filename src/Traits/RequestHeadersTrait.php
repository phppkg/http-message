<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-02-09
 * Time: 16:22
 */

namespace PhpComp\Http\Message\Traits;

/**
 * Trait RequestHeadersTrait
 * @package PhpComp\Http\Message\Traits
 *
 * @property \PhpComp\Http\Message\Headers $headers
 */
trait RequestHeadersTrait
{
    /**
     * @return bool
     */
    public function isWebSocket(): bool
    {
        $val = $this->getHeaderLine('upgrade');

        return \strtolower($val) === 'websocket';
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->isXhr();
    }

    /**
     * Is this an XHR request?
     * Note: This method is not part of the PSR-7 standard.
     * @return bool
     */
    public function isXhr(): bool
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * `Origin: http://foo.example`
     * @return string
     */
    public function getOrigin(): string
    {
        return $this->getHeaderLine('Origin');
    }

    /**
     * Get request content type.
     * Note: This method is not part of the PSR-7 standard.
     * @return string|null The request content type, if known
     */
    public function getContentType(): string
    {
        $result = $this->getHeader('Content-Type');

        return $result ? $result[0] : '';
    }

    /**
     * Get request media type, if known.
     * Note: This method is not part of the PSR-7 standard.
     * @return string The request media type, minus content-type params
     */
    public function getMediaType(): string
    {
        $contentType = $this->getContentType();

        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return '';
    }

    /**
     * Get request media type params, if known.
     * Note: This method is not part of the PSR-7 standard.
     * @return array
     */
    public function getMediaTypeParams(): array
    {
        $contentType = $this->getContentType();
        $contentTypeParams = [];

        if ($contentType) {
            $contentTypeParts = \preg_split('/\s*[;,]\s*/', $contentType);
            $contentTypePartsLength = \count($contentTypeParts);

            for ($i = 1; $i < $contentTypePartsLength; $i++) {
                $paramParts = \explode('=', $contentTypeParts[$i]);
                $contentTypeParams[\strtolower($paramParts[0])] = $paramParts[1];
            }
        }

        return $contentTypeParams;
    }

    /**
     * Get request content character set, if known.
     * Note: This method is not part of the PSR-7 standard.
     * @return string
     */
    public function getContentCharset(): string
    {
        $mediaTypeParams = $this->getMediaTypeParams();

        return $mediaTypeParams['charset'] ?? '';
    }

    /**
     * Get request content length, if known.
     * Note: This method is not part of the PSR-7 standard.
     * @return int
     */
    public function getContentLength(): int
    {
        $result = $this->headers->get('Content-Length');

        return $result ? (int)$result[0] : 0;
    }
}
