<?php

namespace MBO\RemoteGit;

use Psr\Log\LoggerInterface;
use \GuzzleHttp\Client as GuzzleHttpClient;

use MBO\RemoteGit\Github\GithubClient;
use MBO\RemoteGit\Gitlab\GitlabClient;


/**
 * Helper to create clients according to URL
 * 
 * @author mborne
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

        /* Force github URL */
        if ( GithubClient::class === $clientClass ){
            $guzzleOptions['base_uri'] = 'https://api.github.com';
        }

        /* Define auth token */
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
        if ( 'api.github.com' === $hostname || 'github.com' === $hostname ){
            return GithubClient::class;
        }else{
            return GitlabClient::class;
        }
    }

}

