<?php

namespace MBO\SatisGitlab\Git;

use \GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Log\LoggerInterface;

/**
 * Client implementation for github
 */
class GithubClient implements ClientInterface {

    const DEFAULT_PER_PAGE = 100;

    /**
     * @var GuzzleHttpClient
     */
    protected $httpClient;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor with an http client and a logger
     * @param $httpClient http client
     * @param $logger
     */
    public function __construct(
        GuzzleHttpClient $httpClient,
        LoggerInterface $logger
    ){
        $this->httpClient = $httpClient ;
        $this->logger = $logger ;
    }

    /*
     * @{inheritDoc}
     */    
    public function find(array $options){
        /* https://developer.github.com/v3/#pagination */
        $page     = empty($options['page']) ? 1 : $options['page'];
        $perPage = empty($options['per_page']) ? self::DEFAULT_PER_PAGE : $options['per_page'];
        $uri = '?page='.$page.'&per_page='.$perPage;

        $this->logger->debug('GET '.$uri);
        $response = $this->httpClient->get($uri);
        $projects = json_decode( (string)$response->getBody(), true ) ;
        
        $result = array();
        foreach ( $projects as $project ){
            $result[] = new GithubProject($project);
        }
        return $result;
    }

    /*
     * @{inheritDoc}
     */
    public function getRawFile(
        ProjectInterface $project, 
        $filePath,
        $ref
    ){
        $metadata = $project->getRawMetadata();
        $uri = str_replace(
            '{+path}',
            urlencode($filePath),
            $metadata['contents_url']
        );
        $uri .= '?ref='.$ref;
        $this->logger->debug('GET '.$uri);
        $response = $this->httpClient->get($uri,[
            'headers' => [
                'Accept' => 'application/vnd.github.v3.raw'
            ]
        ]);
        return (string)$response->getBody();
    }


}