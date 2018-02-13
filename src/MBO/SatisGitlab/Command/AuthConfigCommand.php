<?php

namespace MBO\SatisGitlab\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Generates auth configuration for gitlab repositories
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class AuthConfigCommand extends Command {
    protected function configure() {
        $this
            ->setName('auth-config')

            // the short description shown while running "php bin/console list"
            ->setDescription('generate authentication configuration for GitLab')
            ->addArgument('gitlab-url', InputArgument::REQUIRED)
            ->addArgument('gitlab-token')

            ->addOption('output', 'O', InputOption::VALUE_REQUIRED, 'output auth config file', 'auth.json')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $gitlabUrl = $input->getArgument('gitlab-url');
        $gitlabAuthToken = $input->getArgument('gitlab-token');
        $outputFile = $input->getOption('output');

        $gitlabDomain = parse_url($gitlabUrl, PHP_URL_HOST);

        $config = array();
        $config['gitlab-domains'] = array($gitlabDomain);
        $config['gitlab-token'][$gitlabDomain] = $gitlabAuthToken;

        $result = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($outputFile, $result);
    }
}
