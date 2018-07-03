<?php

namespace MBO\SatisGitlab\Command;

use Composer\Composer;
use Composer\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Generate SATIS configuration scanning gitlab repositories
 *
 * @author MBorne
 */
class GitlabToConfigCommand extends GitlabCommandBase {

    const DEFAULT_VALUE_HOMEPAGE = 'http://localhost/satis/';

    protected function configure() {
        parent::configure();

        $this
            // the name of the command (the part after "bin/console")
            ->setName('gitlab-to-config')

            // simple customization
            ->addOption('homepage', null, InputOption::VALUE_REQUIRED, 'satis homepage', GitlabCommandBase::DEFAULT_VALUE)
            ->addOption('archive', null, InputOption::VALUE_NONE, 'enable archive mirroring')
        ;
    }

    protected function processProject(InputInterface $input, OutputInterface $output, array &$satis, array $project, array $composer) {
        $project_name = $this->projectName($project, $composer);
        $projectUrl = $project['http_url_to_repo'];

        $satis['repositories'][] = array(
            'type' => 'vcs',
            'url' => $projectUrl,
            //TODO improve SSL management
            'options' => [
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                    "allow_self_signed" => true
                ]
            ]
        );
        $satis['require'][$project_name] = '*';
        $this->displayProjectInfo($output,$project,
            "<info>$project_name:*</info>"
        );
    }

    protected function processSatisConfiguration(InputInterface $input, OutputInterface $output, array &$satis) {
        /*
         * customize according to command line options
         */
        $homepage = $input->getOption('homepage');
        $homepage_default = $homepage === GitlabCommandBase::DEFAULT_VALUE;
        $homepage_empty = !isset($satis['homepage']);
        if ( ! $homepage_default || $homepage_empty ) {
            $satis['homepage'] = ($homepage_default) ? static::DEFAULT_VALUE_HOMEPAGE : $homepage;
        }

        // mirroring
        if ( $input->getOption('archive') ){
            $satis['require-dependencies'] = true;
            $satis['archive'] = array(
                'directory' => 'dist',
                'format' => 'tar',
                'skip-dev' => true
            );
        }
    }

}
