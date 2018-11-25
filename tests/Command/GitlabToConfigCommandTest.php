<?php

namespace MBO\SatisGitlab\Tests\Command;

use MBO\SatisGitlab\Tests\TestCase;

use Symfony\Component\Console\Tester\CommandTester;
use MBO\SatisGitlab\Command\GitlabToConfigCommand;

/**
 * Temporary regress test on gitlab-to-config command to ease refactoring
 */
class GitlabToConfigCommandTest extends TestCase {

    protected $outputFile;

    protected function setUp(){
        $this->outputFile = tempnam(sys_get_temp_dir(),'satis-config');
    }

    protected function tearDown()
    {
        if ( file_exists($this->outputFile) ){ 
            unlink($this->outputFile);
        }
    }
    
    public function testWithFilter(){
        $gitlabToken = getenv('SATIS_GITLAB_TOKEN');
        if ( empty($gitlabToken) ){
            $this->markTestSkipped("Missing SATIS_GITLAB_TOKEN for gitlab.com");
            return;
        }
        $command = new GitlabToConfigCommand('gitlab-to-config');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'gitlab-url' => 'http://gitlab.com',
            'gitlab-token' => $gitlabToken,
            '--projectFilter' => 'sample-composer',
            '--include-if-has-file' => 'README.md',
            '--output' => $this->outputFile
        ));

        $output = $commandTester->getDisplay();
        $this->assertContains(
            'mborne/sample-composer',
            $output
        );

        /* check and remove gitlab-token */
        $result = file_get_contents($this->outputFile);
        $result = json_decode($result,true);
        $this->assertEquals($gitlabToken,$result['config']['gitlab-token']['gitlab.com']);
        $result['config']['gitlab-token']['gitlab.com'] = 'SECRET';

        /* compare complete file */
        $expectedPath = dirname(__FILE__).'/expected-with-filter.json';
        //file_put_contents($expectedPath,json_encode($result,JSON_PRETTY_PRINT));
        $this->assertJsonStringEqualsJsonFile(
            $expectedPath,
            json_encode($result,JSON_PRETTY_PRINT)
        );
    }




}

