<?php

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Tester\CommandTester;

use MBO\SatisGitlab\Filter\IgnoreRegexpFilter;
use MBO\SatisGitlab\Git\ProjectInterface;

/**
 * Test IgnoreRegexpFilter
 */
class IgnoreRegexpFilterTest extends TestCase {

    /**
     * Create a fake project with a given name
     *
     * @param string $projectName
     * @return ProjectInterface
     */
    private function createMockProject($projectName){
        $project = $this->getMockBuilder(ProjectInterface::class)
            ->getMock()
        ;
        $project->expects($this->any())
            ->method('getName')
            ->willReturn($projectName)
        ;
        return $project;
    }

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

