<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client as GuzzleHttpClient;
use MBO\SatisGitlab\Git\GitlabClient;

use Psr\Log\NullLogger;
use MBO\SatisGitlab\Git\ClientclientOptions;
use MBO\SatisGitlab\Git\ClientOptions;


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

        $clientOptions = new ClientOptions();
        $clientOptions
            ->setUrl('https://gitlab.com')
            ->setToken($gitlabToken)
        ;


        /* create client */
        $client = GitlabClient::createClient(
            $clientOptions,
            new NullLogger()
        );

        /* search projects */
        $projects = $client->find(array(
            'search' => 'sample-composer'
        ));
        $projectsByName = array();
        foreach ( $projects as $project ){
            $projectsByName[$project->getName()] = $project;
        }
        /* check project found */
        $this->assertArrayHasKey(
            'mborne/sample-composer',
            $projectsByName
        );

        $project = $projectsByName['mborne/sample-composer'];
        $composer = $client->getRawFile(
            $project,
            'composer.json',
            $project->getDefaultBranch()
        );
        $this->assertContains('mborne@users.noreply.github.com',$composer);
    }

}
