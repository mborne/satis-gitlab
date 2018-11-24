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
    public function find(FindOptions $options, $page = 1){
        /* https://developer.github.com/v3/#pagination */
        $uri = '?page='.$page.'&per_page='.self::DEFAULT_PER_PAGE;

        $this->logger->debug('GET '.$uri);
        $response = $this->httpClient->get($uri);
        $rawProjects = json_decode( (string)$response->getBody(), true ) ;
        
        $result = array();
        foreach ( $rawProjects as $rawProject ){
            $project = new GithubProject($rawProject);
            if ( ! $options->getFilterCollection()->isAccepted($project) ){
                continue;
            }
            $result[] = $project;
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