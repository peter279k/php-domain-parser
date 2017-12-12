<?php
/**
 * PHP Domain Parser: Public Suffix List based URL parsing.
 *
 * @see http://github.com/jeremykendall/php-domain-parser for the canonical source repository
 *
 * @copyright Copyright (c) 2017 Jeremy Kendall (http://jeremykendall.net)
 * @license   http://github.com/jeremykendall/php-domain-parser/blob/master/LICENSE MIT License
 */
declare(strict_types=1);

namespace Pdp;

use InvalidArgumentException;

/**
 * Simple cURL Http client
 *
 * Lifted pretty much completely from William Durand's excellent Geocoder
 * project
 *
 * @see https://github.com/willdurand/Geocoder Geocoder on GitHub
 *
 * @author William Durand <william.durand1@gmail.com>
 * @author Jeremy Kendall <jeremy@jeremykendall.net>
 * @author Ignace Nyamagana Butera <nyamsprod@gmail.com>
 */
final class CurlHttpClient implements HttpClient
{
    /**
     * Additionnal cURL options
     *
     * @var array
     */
    private $options;

    /**
     * new instance
     *
     * @param array $options additional cURL options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options + [
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
        ];

        $curl = curl_init();
        $res = @curl_setopt_array($curl, $this->options);
        curl_close($curl);
        if (!$res) {
            throw new InvalidArgumentException('Please verify your curl additionnal options');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(string $url): string
    {
        $options = $this->options;
        $options[CURLOPT_URL] = $url;
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $content = curl_exec($curl);
        $code = curl_errno($curl);
        $message = curl_error($curl);
        curl_close($curl);
        if (CURLE_OK === $code) {
            return $content;
        }

        throw new HttpClientException($message, $code);
    }
}