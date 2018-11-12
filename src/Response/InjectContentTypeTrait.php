<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午12:25
 */

namespace PhpComp\Http\Message\Response;

/**
 * Trait InjectContentTypeTrait
 * @package PhpComp\Http\Message\Response
 * @from https://github.com/zendframework/zend-diactoros/blob/master/src/Response/InjectContentTypeTrait.php
 */
trait InjectContentTypeTrait
{
    /**
     * Inject the provided Content-Type, if none is already present.
     *
     * @param string $contentType
     * @param array $headers
     * @return array Headers with injected Content-Type
     */
    private function injectContentType(string $contentType, array $headers): array
    {
        $hasContentType = \array_reduce(\array_keys($headers), function ($carry, $item) {
            return $carry ?: (\strtolower($item) === 'content-type');
        }, false);

        if (!$hasContentType) {
            $headers['content-type'] = [$contentType];
        }

        return $headers;
    }
}
