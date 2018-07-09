<?php

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Tester\CommandTester;
use MBO\SatisGitlab\Command\GitlabToConfigCommand;

/**
 * Regress test on gitlab-to-config command
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
    
    public function testRegressGitlabWithProjectFilter(){
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
            '--output' => $this->outputFile
        ));

        $output = $commandTester->getDisplay();
        $this->assertContains(
            'mborne/sample-composer',
            $output
        );

        $result = file_get_contents($this->outputFile);
        $result = json_decode($result,true);
        $this->assertEquals('http://localhost/satis/',$result['homepage']);
    }

}

