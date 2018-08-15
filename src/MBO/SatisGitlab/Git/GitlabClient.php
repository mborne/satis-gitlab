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
class GitlabClient implements ClientInterface {

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
     * Create GitlabClient with options
     *
     * @param ClientOptions $options
     * @param LoggerInterface $logger
     * @return GitlabClient
     */
    public static function createClient(
        ClientOptions $options,
        LoggerInterface $logger
    ) {
        /* create http client for gitlab */
        $guzzleOptions = array(
            'base_uri' => $options->getUrl(),
            'timeout'  => 10.0,
            'headers' => []
        );
        if ( $options->isUnsafeSsl() ){
            $guzzleOptions['verify'] = false;
        }
        if ( $options->hasToken() ){
            $guzzleOptions['headers']['Private-Token'] = $options->getToken();
        }
        $httpClient = new GuzzleHttpClient($guzzleOptions);

        /* create gitlab client */
        return new GitlabClient($httpClient,$logger);
    }

    /*
     * @{inheritDoc}
     */    
    public function find(array $options){
        /*
         * refs : 
         * https://docs.gitlab.com/ee/api/projects.html#list-all-projects
         * https://docs.gitlab.com/ee/api/projects.html#search-for-projects-by-name
         */
        $page     = empty($options['page']) ? 1 : $options['page'];
        $perPage = empty($options['per_page']) ? self::DEFAULT_PER_PAGE : $options['per_page'];
        $uri = '/api/v4/projects?page='.$page.'&per_page='.$perPage;
        if ( ! empty($options['search']) ){
            $uri .= '&search='.$options['search'];
        }
        $this->logger->debug('GET '.$uri);
        $response = $this->httpClient->get($uri);
        $projects = json_decode( (string)$response->getBody(), true ) ;
        
        $result = array();
        foreach ( $projects as $project ){
            $result[] = new GitlabProject($project);
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
        // ref : https://docs.gitlab.com/ee/api/repository_files.html#get-raw-file-from-repository
        $uri  = '/api/v4/projects/'.$project->getId().'/repository/files/'.urlencode($filePath).'/raw';
        $uri .= '?ref='.$ref;
        $this->logger->debug('GET '.$uri);
        $response = $this->httpClient->get($uri);
        return (string)$response->getBody();
    }


}