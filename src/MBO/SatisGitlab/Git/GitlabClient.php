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

    /*
     * @{inheritDoc}
     */    
    public function find(FindOptions $options, $page=1){
        /*
         * refs : 
         * https://docs.gitlab.com/ee/api/projects.html#list-all-projects
         * https://docs.gitlab.com/ee/api/projects.html#search-for-projects-by-name
         */
        $uri = '/api/v4/projects?page='.$page.'&per_page='.self::DEFAULT_PER_PAGE;
        if ( $options->hasSearch() ){
            $uri .= '&search='.$options->getSearch();
        }
        $this->logger->debug('GET '.$uri);
        $response = $this->httpClient->get($uri);
        $projects = json_decode( (string)$response->getBody(), true ) ;
        
        $result = array();
        foreach ( $projects as $project ){
            $project = new GitlabProject($project);
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
        // ref : https://docs.gitlab.com/ee/api/repository_files.html#get-raw-file-from-repository
        $uri  = '/api/v4/projects/'.$project->getId().'/repository/files/'.urlencode($filePath).'/raw';
        $uri .= '?ref='.$ref;
        $this->logger->debug('GET '.$uri);
        $response = $this->httpClient->get($uri);
        return (string)$response->getBody();
    }


}