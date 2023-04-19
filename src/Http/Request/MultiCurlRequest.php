<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Request;

use ArrayIterator\Reactor\Source\Events\Interfaces\EventsManagerInterface;
use ArrayIterator\Rev\Source\Http\Request\Exceptions\BadResponseException;
use Psr\Http\Message\UriInterface;
use Throwable;

class MultiCurlRequest
{
    /**
     * @var array<string, Curl|ResponseResult|BadResponseException>
     */
    private array $curl = [];
    private array $keys = [];

    public function addRequest(
        UriInterface|string $uri,
        string $method = 'GET',
        array $options = [],
        ?EventsManagerInterface $eventsManager = null
    ): string {
        $client = Curl::createRequest($uri, $method, $options, $eventsManager);
        $key = spl_object_hash($client);
        $this->add($key, $client);
        return $key;
    }

    /**
     * @param string $key
     * @param Curl $curl
     */
    public function add(string $key, Curl $curl): void
    {
        $id               = spl_object_hash($curl);
        $this->keys[$key] = $id;
        $this->curl[$id] = $curl;
    }

    /**
     * @return array<ResponseResult|BadResponseException>
     */
    public function send(): array
    {
        if (empty($this->curl)) {
            return [];
        }

        $response = [];
        /**
         * @var Curl $curl
         */
        $handles = [];
        foreach ($this->curl as $key => $curl) {
            $result = $curl->getResult();
            if ($result) {
                $this->curl[$key] = $result;
                continue;
            }
            $handles[$key] = $curl->getResource();
        }

        if (!empty($handles)) {
            $mh = curl_multi_init();
            foreach ($handles as $handle) {
                curl_multi_add_handle($mh, $handle);
            }
            do {
                $status = curl_multi_exec($mh, $active);
                if ($active) {
                    curl_multi_select($mh);
                }
            } while ($active && $status == CURLM_OK);
            foreach ($handles as $key => $handle) {
                try {
                    $this->curl[$key] = $this->curl[$key]->formatInternal(
                        curl_getinfo($handle),
                        curl_errno($handle),
                        curl_error($handle)
                    );
                } catch (Throwable $e) {
                    $this->curl[$key] = $e;
                }

                curl_multi_remove_handle($mh, $handle);
                unset($handles[$key]);
            }
            curl_multi_close($mh);
            unset($mh);
        }

        foreach ($this->keys as $key => $id) {
            $response[$key] = $this->curl[$id];
        }

        return $response;
    }
}
