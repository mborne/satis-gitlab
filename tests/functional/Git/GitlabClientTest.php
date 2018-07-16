<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client as GuzzleHttpClient;
use MBO\SatisGitlab\Git\GitlabClient;

use Psr\Log\NullLogger;


class GitlabClientTest extends TestCase {

    /**
     * Ensure client can find mborne/sample-composer
     */
    public function testGitlabDotComAuthenticated(){
        $gitlabToken = getenv('SATIS_GITLAB_TOKEN');
        if ( empty($gitlabToken) ){
            $this->markTestSkipped("Missing SATIS_GITLAB_TOKEN for gitlab.com");
            return;
        }

        /* create client */
        $client = GitlabClient::createClient(
            'https://gitlab.com',
            $gitlabToken,
            false,
            new NullLogger()
        );

        /* search projects */
        $projects = $client->find(array(
            'search' => 'sample-composer'
        ));
        $projectsByName = array();
        foreach ( $projects as $project ){
            $projectsByName[$project['path_with_namespace']] = $project;
        }
        /* check project found */
        $this->assertArrayHasKey(
            'mborne/sample-composer',
            $projectsByName
        );

        $project = $projectsByName['mborne/sample-composer'];
        $composer = $client->getRawFile(
            $project['id'],
            'composer.json',
            $project['default_branch']
        );
        $this->assertContains('mborne@users.noreply.github.com',$composer);
    }

}
