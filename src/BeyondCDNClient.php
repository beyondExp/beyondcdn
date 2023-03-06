<?php

namespace Beyond\BeyondCDN;

use Beyond\BeyondCDN\Exceptions\BeyondCDNException;
use Beyond\BeyondCDN\Exceptions\DirectoryNotEmptyException;
use Beyond\BeyondCDN\Exceptions\NotFoundException;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\GuzzleException;

class BeyondCDNClient
{
    public $storage_zone_name;
    private $api_key;
    private $region;

    public $client;

    public function __construct(string $storage_zone_name, string $api_key, string $region = BeyondCDNRegion::FALKENSTEIN)
    {
        $this->storage_zone_name = $storage_zone_name;
        $this->api_key = $api_key;
        $this->region = $region;

        $this->client = new Guzzle();
    }

    private static function get_base_url($region): string
    {
        switch ($region) {
            case BeyondCDNRegion::NEW_YORK:
                return 'https://ny.storage.beyondcdn.com/';
            case BeyondCDNRegion::LOS_ANGELAS:
                return 'https://la.storage.beyondcdn.com/';
            case BeyondCDNRegion::SINGAPORE:
                return 'https://sg.storage.beyondcdn.com/';
            case BeyondCDNRegion::SYDNEY:
                return 'https://syd.storage.beyondcdn.com/';
            case BeyondCDNRegion::UNITED_KINGDOM:
                return 'https://uk.storage.beyondcdn.com/';
            case BeyondCDNRegion::STOCKHOLM:
                return 'https://se.storage.beyondcdn.com/';
            default:
                return 'https://storage.beyondcdn.com/';
        }
    }

    /**
     * @throws GuzzleException
     */
    private function request(string $path, string $method = 'GET', array $options = [])
    {
        $response = $this->client->request(
            $method,
            self::get_base_url($this->region) . Util::normalizePath('/' . $this->storage_zone_name . '/').$path,
            array_merge_recursive([
                'headers' => [
                    'Accept' => '*/*',
                    'AccessKey' => $this->api_key, # Honestly... Why do I have to specify this twice... @BunnyCDN
                ],
            ], $options)
        );

        $contents = $response->getBody()->getContents();

        return json_decode($contents, true) ?? $contents;
    }

    /**
     * @param string $path
     * @return array
     * @throws \Beyond\BeyondCDN\Exceptions\NotFoundException|BeyondCDNException
     */
    public function list(string $path): array
    {
        try {
            $listing = $this->request(Util::normalizePath($path).'/');

            # Throw an exception if we don't get back an array
            if(!is_array($listing)) { throw new NotFoundException('File is not a directory'); }

            return array_map(function($bunny_cdn_item) {
                return $bunny_cdn_item;
            }, $listing);
        } catch (GuzzleException $e) {
            if($e->getCode() === 404) {
                throw new NotFoundException($e->getMessage());
            } else {
                throw new BeyondCDNException($e->getMessage());
            }
        }
    }

    /**
     * @param string $path
     * @return mixed
     * @throws BeyondCDNException
     * @throws NotFoundException
     */
    public function download(string $path): string
    {
        try {
            $content = $this->request($path . '?download');

            if (\is_array($content)) {
                return \json_encode($content);
            }

            return $content;
        } catch (GuzzleException $e) {
            if($e->getCode() === 404) {
                throw new NotFoundException($e->getMessage());
            }

            throw new BeyondCDNException($e->getMessage());
        }
    }

    /**
     * @param string $path
     * @return resource|null
     * @throws BeyondCDNException
     * @throws NotFoundException
     */
    public function stream(string $path)
    {
        try {
            return $this->client->request(
                'GET',
                self::get_base_url($this->region) . Util::normalizePath('/' . $this->storage_zone_name . '/').$path,
                array_merge_recursive([
                    'stream' => true,
                    'headers' => [
                        'Accept' => '*/*',
                        'AccessKey' => $this->api_key, # Honestly... Why do I have to specify this twice... @BunnyCDN
                    ]
                ])
            )->getBody()->detach();
            // @codeCoverageIgnoreStart
        } catch (GuzzleException $e) {
            if($e->getCode() === 404) {
                throw new NotFoundException($e->getMessage());
            } else {
                throw new BeyondCDNException($e->getMessage());
            }
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param string $path
     * @param $contents
     * @return mixed
     * @throws BeyondCDNException
     */
    public function upload(string $path, $contents)
    {
        try {
            return $this->request($path, 'PUT', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                ],
                'body' => $contents
            ]);
        } catch (GuzzleException $e) {
            throw new BeyondCDNException($e->getMessage());
        }
    }

    /**
     * @param string $path
     * @return mixed
     * @throws BeyondCDNException
     */
    public function make_directory(string $path)
    {
        try {
            return $this->request(Util::normalizePath($path).'/', 'PUT', [
                'headers' => [
                    'Content-Length' => 0
                ],
            ]);
        } catch (GuzzleException $e) {
            if($e->getCode() === 400) {
                throw new BeyondCDNException('Directory already exists');
            } else {
                throw new BeyondCDNException($e->getMessage());
            }
        }
    }

    /**
     * @param string $path
     * @return mixed
     * @throws NotFoundException
     * @throws DirectoryNotEmptyException|BeyondCDNException
     */
    public function delete(string $path)
    {
        try {
            return $this->request($path, 'DELETE');
        } catch (GuzzleException $e) {
            if($e->getCode() === 404) {
                throw new NotFoundException($e->getMessage());
            } elseif($e->getCode() === 400) {
                throw new DirectoryNotEmptyException($e->getMessage());
            } else {
                throw new BeyondCDNException($e->getMessage());
            }
        }
    }
}