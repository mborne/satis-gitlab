<?php

use PHPUnit\Framework\TestCase;
use MBO\SatisGitlab\Git\GitlabProjectScanner;

use GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Log\NullLogger;

class GitlabProjectScannerTest extends TestCase {

    /**
     * @return GitlabProjectScanner
     */
    protected function createProjectScanner($gitlabToken){
        $guzzleOptions = [
            'base_uri' => 'https://gitlab.com',
            'timeout'  => 10.0,
            'headers' => [
                'Private-Token' => $gitlabToken
            ]
        ];
        $httpClient = new GuzzleHttpClient($guzzleOptions);
        $logger = new NullLogger();
        return new GitlabProjectScanner($httpClient,$logger);
    }

    /**
     * Ensure client can find mborne/sample-composer
     */
    public function testGitlabDotComAuthenticated(){
        $gitlabToken = getenv('SATIS_GITLAB_TOKEN');
        if ( empty($gitlabToken) ){
            $this->markTestSkipped("Missing SATIS_GITLAB_TOKEN for gitlab.com");
            return;
        }
        $scanner = $this->createProjectScanner($gitlabToken);

        $search = 'sample-composer';
        $projects = array();
        $scanner->find(function($project) use (&$projects){
            $projects[$project['path_with_namespace']] = $project;
        },$search);
        
        $this->assertArrayHasKey(
            'mborne/sample-composer',
            $projects
        );

        $project = $projects['mborne/sample-composer'];
        $composer = $scanner->getRawFile(
            $project['id'],
            'composer.json',
            $project['default_branch']
        );
        $this->assertContains('mborne@users.noreply.github.com',$composer);
    }

}
