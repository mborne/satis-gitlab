<?php

namespace MBO\SatisGitlab\Git;

use \GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Log\LoggerInterface;

/**
 * Find gitlab projects
 */
class GitlabProjectScanner {

    const PER_PAGE = 50;
    const MAX_PAGES = 10000;

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
     * Find projects throw gitlab API
     * 
     * TODO $options instead of $search
     * 
     * see https://docs.gitlab.com/ee/api/projects.html#search-for-projects-by-name for search (group option would be better?)
     * 
     * https://docs.gitlab.com/ee/api/projects.html#list-all-projects
     */
    public function find($callback, $search = null){
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $uri = '/api/v4/projects?page='.$page.'&per_page='.self::PER_PAGE;
            if ( ! empty($search) ){
                $uri .= '&search='.$search;
            }
            $this->logger->debug('GET '.$uri);
            $response = $this->httpClient->get($uri);
            $projects = json_decode( (string)$response->getBody(), true ) ;
            if ( empty($projects) ){
                return;
            }
            foreach ($projects as $project) {
                $callback($project);
            }
        }
    }

    /**
     * 
     * https://docs.gitlab.com/ee/api/repository_files.html#get-raw-file-from-repository
     * 
     * @param string $projectId ex : 123456
     * @param string $filePath ex : composer.json
     * @param string $ref ex : master
     */
    public function getRawFile(
        $projectId, 
        $filePath,
        $ref
    ){
        $uri  = '/api/v4/projects/'.$projectId.'/repository/files/'.urlencode($filePath).'/raw';
        $uri .= '?ref='.$ref;
        $response = $this->httpClient->get($uri);
        return (string)$response->getBody();
    }


}