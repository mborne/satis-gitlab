<?php

namespace MBO\SatisGitlab\Command;

use Composer\Composer;
use Composer\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use MBO\SatisGitlab\Satis\ConfigBuilder;

/**
 * Generate SATIS configuration scanning gitlab repositories
 *
 * @author MBorne
 */
class GitlabToConfigCommand extends Command {

    const PER_PAGE = 50;
    const MAX_PAGES = 10000;

    protected function configure() {
        $templatePath = realpath( dirname(__FILE__).'/../Resources/default-template.json' );

        $this
            // the name of the command (the part after "bin/console")
            ->setName('gitlab-to-config')

            // the short description shown while running "php bin/console list"
            ->setDescription('generate satis configuration scanning gitlab repositories')
            ->setHelp('look for composer.json in default gitlab branche, extract project name and register them in SATIS configuration')
            
            /* 
             * project listing options 
             */
            ->addArgument('gitlab-url', InputArgument::REQUIRED)
            ->addArgument('gitlab-token')
            ->addOption('projectFilter', 'p', InputOption::VALUE_OPTIONAL, 'filter for projects', null)

            /* 
             * satis config generation options 
             */
            // deep customization : template file extended with default configuration
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'template satis.json extended with gitlab repositories', $templatePath)

            // simple customization
            ->addOption('homepage', null, InputOption::VALUE_REQUIRED, 'satis homepage')
            ->addOption('archive', null, InputOption::VALUE_NONE, 'enable archive mirroring')
            ->addOption('no-token', null, InputOption::VALUE_NONE, 'disable token writing in output configuration')

            /* 
             * output options
             */
            ->addOption('output', 'O', InputOption::VALUE_REQUIRED, 'output config file', 'satis.json')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /*
         * parameters
         */
        $gitlabUrl = $input->getArgument('gitlab-url');
        $gitlabAuthToken = $input->getArgument('gitlab-token');
        /*
         * TODO add option 
         * see https://github.com/mborne/satis-gitlab/issues/2
         */
        $gitlabUnsafeSsl = true;

        $outputFile = $input->getOption('output');
        $projectFilter = $input->getOption('projectFilter');

        /*
         * Create configuration builder
         */
        $templatePath = $input->getOption('template');
        $output->writeln(sprintf("<info>Loading template %s...</info>", $templatePath));
        $configBuilder = new ConfigBuilder($templatePath);

        /*
         * customize according to command line options
         */
        $homepage = $input->getOption('homepage');
        if ( ! empty($homepage) ){
            $configBuilder->setHomepage($homepage);
        }

        // mirroring
        if ( $input->getOption('archive') ){
            $configBuilder->enableArchive();
        }

        /*
         * Register gitlab domain to enable composer gitlab-* authentications
         */
        $gitlabDomain = parse_url($gitlabUrl, PHP_URL_HOST);
        $configBuilder->addGitlabDomain($gitlabDomain);

        if ( ! $input->getOption('no-token') && ! empty($gitlabAuthToken) ){
            $configBuilder->addGitlabToken(
                $gitlabDomain, 
                $gitlabAuthToken,
                $gitlabUnsafeSsl
            );
        }

        /*
         * SCAN gitlab projects to find composer.json file in default branch
         */
        $output->writeln(sprintf("<info>Listing gitlab repositories from %s...</info>", $gitlabUrl));
        $client = $this->createGitlabClient(
            $gitlabUrl,
            $gitlabAuthToken,
            $gitlabUnsafeSsl
        );

        $projectCriteria = array(
            'page' => 1,
            'per_page' => self::PER_PAGE
        );

        if ($projectFilter !== null) {
            $projectCriteria['search'] = $projectFilter;
            $output->writeln(sprintf("<info>Applying project filter %s...</info>", $projectFilter));
        }

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $projectCriteria['page'] = $page;

            $projects = $client->projects()->all($projectCriteria);
            if ( empty($projects) ){
                break;
            }
            foreach ($projects as $project) {
                $projectUrl = $project['http_url_to_repo'];
                try {
                    $json = $client->repositoryFiles()->getRawFile($project['id'], 'composer.json', $project['default_branch']);
                    $composer = json_decode($json, true);

                    $projectName = isset($composer['name']) ? $composer['name'] : null;
                    if (is_null($projectName)) {
                        $this->displayProjectInfo($output,$project,'<error>name not defined in composer.json</error>');
                        continue;
                    }

                    $this->displayProjectInfo($output,$project,
                        "<info>$projectName:*</info>"
                    );
                    $configBuilder->addRepository(
                        $projectName, 
                        $projectUrl,
                        $gitlabUnsafeSsl
                    );
                } catch (\Exception $e) {
                    $this->displayProjectInfo($output,$project,
                        'composer.json not found',
                        OutputInterface::VERBOSITY_VERBOSE
                    );
                }
            }
        }

        /* get resulting configuration */
        $satis = $configBuilder->getConfig();
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
     * 
     * @param string $gitlabUrl
     * @param string $gitlabAuthToken
     * @param boolean $gitlabUnsafeSsl
     * 
     * @return \Gitlab\Client
     */
    protected function createGitlabClient(
        $gitlabUrl, 
        $gitlabAuthToken,
        $gitlabUnsafeSsl
    ) {
        $guzzleOptions = array();
        if ( $gitlabUnsafeSsl ){
            $guzzleOptions['verify'] = false;
        }
        
        /*
         * Create HTTP client according to $gitlabUnsafeSsl
         */
        $guzzleClient = new \GuzzleHttp\Client($guzzleOptions);
        $httpClient = new \Http\Adapter\Guzzle6\Client($guzzleClient);
        $httpClientBuilder = new \Gitlab\HttpClient\Builder($httpClient);

        /*
         * Create gitlab client
         */
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
