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
 * Define satis reusable base command using gitlab configuration
 *
 * @author Rich Gerdes
 */
class GitlabCommandBase extends Command {

    const PER_PAGE = 50;
    const MAX_PAGES = 10000;
    const DEFAULT_VALUE = '_default_';
    const DEFAULT_VALUE_GITLAB_URL = 'https://gitlab.com';

    protected function configure() {
        $templatePath = realpath( dirname(__FILE__).'/../Resources/default-template.json' );

        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('populate satis required packages by scanning gitlab repositories')
            ->setHelp('look for composer.json in default gitlab branche, extract dependencies and register them in SATIS configuration')
            ->addArgument('gitlab-url', InputArgument::OPTIONAL, 'gitlab instance url', static::DEFAULT_VALUE)
            ->addArgument('gitlab-token')

            // deep customization : template file extended with default configuration
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'template satis.json extended with gitlab repositories', $templatePath)

            ->addOption('no-token', null, InputOption::VALUE_NONE, 'disable token writing in output configuration')

            // output configuration
            ->addOption('output', 'O', InputOption::VALUE_REQUIRED, 'output config file', 'satis.json')
        ;
    }

    protected function projectName(array $project, array $composer) {
        $project_name = isset($composer['name']) ? $composer['name'] : null;
        if (is_null($project_name)) {
            // User project path as name if composer.json does not have one.
            $project_name = $project['path_with_namespace'];
            $count_slashes = substr_count($project_name, '/');
            if ($count_slashes > 0) {
                // if there is more then one slash, replace all but last
                $project_name = str_replace ('/', $project_name, '-', $count_slashes - 1);
            }
        }
        return $project_name;
    }

    protected function processProject(InputInterface $input, OutputInterface $output, array &$satis, array $project, array $composer) {
       // Function not required by default.
    }

    protected function processSatisConfiguration(InputInterface $input, OutputInterface $output, array &$satis) {
       // Function not required by default.
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        /*
         * load template satis.json file
         */
        $templatePath = $input->getOption('template');
        $output->writeln(sprintf("<info>Loading template %s...</info>", $templatePath));
        $satis = json_decode( file_get_contents($templatePath), true) ;

        /*
         * parameters
         */
        $gitlabUrl = $input->getArgument('gitlab-url');
        if ( $gitlabUrl === static::DEFAULT_VALUE ) {
            $gitlabUrlSet = isset($satis['config']['gitlab-domains']);
            $gitlabUrlSet = $gitlabUrlSet && is_array($satis['config']['gitlab-domains']);
            $gitlabUrlSet = $gitlabUrlSet && ! empty($satis['config']['gitlab-domains']);
            if ($gitlabUrlSet) {
                // if there is a gitlab domain already configured use it.
                $gitlabUrl = 'https://' . reset($satis['config']['gitlab-domains']);
            } else {
                $gitlabUrl = static::DEFAULT_VALUE_GITLAB_URL;
            }
        }
        $gitlabAuthToken = $input->getArgument('gitlab-token');
        $outputFile = $input->getOption('output');

        /*
         * Register gitlab domain to enable composer gitlab-* authentications
         */
        $gitlabDomain = parse_url($gitlabUrl, PHP_URL_HOST);
        if ( ! isset($satis['config']) ){
            $satis['config'] = array();
        }
        if ( ! isset($satis['config']['gitlab-domains']) ){
            $satis['config']['gitlab-domains'] = array($gitlabDomain);
        } else {
            $satis['config']['gitlab-domains'][] = $gitlabDomain ;
        }

        if ( ! $input->getOption('no-token') && ! empty($gitlabAuthToken) ){
            if ( ! isset($satis['config']['gitlab-token']) ){
                $satis['config']['gitlab-token'] = array();
            }
            $satis['config']['gitlab-token'][$gitlabDomain] = $gitlabAuthToken;
        }

        $this->processSatisConfiguration($input, $output, $satis);

        /*
         * SCAN gitlab projects to find composer.json file in default branch
         */
        $output->writeln(sprintf("<info>Listing gitlab repositories from %s...</info>", $gitlabUrl));
        $client = $this->createGitlabClient($gitlabUrl, $gitlabAuthToken);
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $projects = $client->projects()->all(array(
                'page' => $page,
                'per_page' => self::PER_PAGE
            ));
            if ( empty($projects) ){
                break;
            }
            foreach ($projects as $project) {
                try {
                    $json = $client->repositoryFiles()->getRawFile($project['id'], 'composer.json', $project['default_branch']);
                    $composer = json_decode($json, true);

                    $this->processProject($input, $output, $satis, $project, $composer);
                } catch (\Exception $e) {
                    $this->displayProjectInfo(
                        $output,
                        $project,
                        'composer.json not found',
                        OutputInterface::VERBOSITY_VERBOSE
                    );
                }
            }
        }

        $output->writeln("<info>generate satis configuration file : $outputFile</info>");
        $result = json_encode($satis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($outputFile, $result);
    }

    /**
     * display project information
     */
    protected function displayProjectInfo(
        OutputInterface $output,
        array $project,
        $message,
        $verbosity = OutputInterface::VERBOSITY_NORMAL
    ){
        $output->writeln(sprintf(
            '%s (branch %s) : %s',
            $project['name_with_namespace'],
            $project['default_branch'],
            $message
        ),$verbosity);
    }


    /**
     * Create gitlab client
     * @param string $gitlabUrl
     * @param string $gitlabAuthToken
     * @return \Gitlab\Client
     */
    protected function createGitlabClient($gitlabUrl, $gitlabAuthToken) {
        /*
         * create client with ssl verify disabled
         */
        $guzzleClient = new \GuzzleHttp\Client(array(
            'verify' => false
        ));
        $httpClient = new \Http\Adapter\Guzzle6\Client($guzzleClient);
        $httpClientBuilder = new \Gitlab\HttpClient\Builder($httpClient);

        $client = new \Gitlab\Client($httpClientBuilder);
        $client->setUrl($gitlabUrl);

        // Authenticate to gitlab, if a token is provided
        if ( ! empty($gitlabAuthToken) ) {
            $client
                ->authenticate($gitlabAuthToken, \Gitlab\Client::AUTH_URL_TOKEN)
            ;
        }

        return $client;
    }

}
