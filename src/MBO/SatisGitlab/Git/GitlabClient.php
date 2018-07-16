<?php

namespace MBO\SatisGitlab\Git;

use \GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Log\LoggerInterface;

/**
 * Find gitlab projects
 * 
 * TODO add ClientInterface and RepositoryInterface to allow github, gogs and local repositories & co?
 * 
 */
class GitlabClient {

    const DEFAULT_PER_PAGE = 50;

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

    /**
     * Create gitlab client
     * 
     * @param string $gitlabUrl
     * @param string $gitlabAuthToken
     * @param boolean $gitlabUnsafeSsl
     * 
     * @return GitlabClient
     */
    public static function createClient(
        $gitlabUrl, 
        $gitlabAuthToken,
        $gitlabUnsafeSsl,
        LoggerInterface $logger
    ) {
        /* create http client for gitlab */
        $guzzleOptions = array(
            'base_uri' => $gitlabUrl,
            'timeout'  => 10.0,
            'headers' => []
        );
        if ( $gitlabUnsafeSsl ){
            $guzzleOptions['verify'] = false;
        }
        if ( ! empty($gitlabAuthToken) ){
            $guzzleOptions['headers']['Private-Token'] = $gitlabAuthToken;
        }
        $httpClient = new GuzzleHttpClient($guzzleOptions);

        /* create gitlab client */
        return new GitlabClient($httpClient,$logger);
    }


    /**
     * Find projects throw gitlab API
     * 
     * see https://docs.gitlab.com/ee/api/projects.html#search-for-projects-by-name for search (group option would be better?)
     * 
     * https://docs.gitlab.com/ee/api/projects.html#list-all-projects
     * 
     * @return array
     */
    public function find(array $options){
        $page     = empty($options['page']) ? 1 : $options['page'];
        $perPage = empty($options['per_page']) ? self::DEFAULT_PER_PAGE : $options['per_page'];
        $uri = '/api/v4/projects?page='.$page.'&per_page='.$perPage;
        if ( ! empty($options['search']) ){
            $uri .= '&search='.$options['search'];
        }
        $this->logger->debug('GET '.$uri);
        $response = $this->httpClient->get($uri);
        $projects = json_decode( (string)$response->getBody(), true ) ;
        return $projects;
    }

    /**
     * 
     * https://docs.gitlab.com/ee/api/repository_files.html#get-raw-file-from-repository
     * 
     * @param string $projectId ex : 123456
     * @param string $filePath ex : composer.json
     * @param string $ref ex : master
     * 
     * @return string
     */
    public function getRawFile(
        $projectId, 
        $filePath,
        $ref
    ){
        $uri  = '/api/v4/projects/'.$projectId.'/repository/files/'.urlencode($filePath).'/raw';
        $uri .= '?ref='.$ref;
        $this->logger->debug('GET '.$uri);
        $response = $this->httpClient->get($uri);
        return (string)$response->getBody();
    }


}