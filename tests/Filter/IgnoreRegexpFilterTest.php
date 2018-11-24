<?php

namespace Tests\SatisGitlab\Filter;

use Tests\SatisGitlab\TestCase;

use Symfony\Component\Console\Tester\CommandTester;

use MBO\RemoteGit\Filter\IgnoreRegexpFilter;
use MBO\RemoteGit\ProjectInterface;

/**
 * Test IgnoreRegexpFilter
 */
class IgnoreRegexpFilterTest extends TestCase {

    public function testExample(){
        $filter = new IgnoreRegexpFilter('(^phpstorm|^typo3\/library)');

        $expectedResults = array(
            'mborne/sample-project' => true,
            'something' => true,
            'meuh' => true,
            'phpstorm/something' => false
        );

        foreach ( $expectedResults as $projectName => $expected ){
            $project = $this->createMockProject($projectName);
            $this->assertTrue(
                $filter->isAccepted($project) === $expected,
                'unexpected result for '.$projectName
            );
        }
        
    }

}

