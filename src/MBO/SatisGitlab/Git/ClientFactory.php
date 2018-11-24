<?php

namespace MBO\SatisGitlab\Git;

use Psr\Log\LoggerInterface;
use \GuzzleHttp\Client as GuzzleHttpClient;

/**
 * Helper to create clients
 */
class ClientFactory {

    /**
     * Create a client with options
     *
     * @param ClientOptions $options
     * @param LoggerInterface $logger
     * @return ClientInterface
     */
    public static function createClient(
        ClientOptions $options,
        LoggerInterface $logger
    ) {
        $clientClass = self::detectClientClass($options->getUrl());

        /* common http options */
        $guzzleOptions = array(
            'base_uri' => $options->getUrl(),
            'timeout'  => 10.0,
            'headers' => []
        );
        if ( $options->isUnsafeSsl() ){
            $guzzleOptions['verify'] = false;
        }
        if ( $options->hasToken() ){
            if ( GitlabClient::class === $clientClass ){
                $guzzleOptions['headers']['Private-Token'] = $options->getToken();
            }else if ( GithubClient::class === $clientClass ){
                $guzzleOptions['headers']['Authorization'] = 'token '.$options->getToken();
            }
        }

        $httpClient = new GuzzleHttpClient($guzzleOptions);

        /* create gitlab client */
        return new $clientClass($httpClient,$logger);
    }

    /**
     * Get client class according to URL content
     *
     * @param string $url
     * @return string
     */
    public static function detectClientClass($url){
        $hostname = parse_url($url, PHP_URL_HOST);
        if ( 'api.github.com' === $hostname ){
            return GithubClient::class;
        }else{
            return GitlabClient::class;
        }
    }

}

