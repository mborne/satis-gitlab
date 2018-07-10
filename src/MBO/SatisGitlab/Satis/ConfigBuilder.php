<?php

namespace MBO\SatisGitlab\Satis;

/**
 * Incremental satis configuration builder
 */
class ConfigBuilder {

    /**
     * resulting configuration
     */
    protected $config ;

    /**
     * Init configuration with a template
     * @param $templatePath string path to the template
     */
    public function __construct( $templatePath = null )
    {
        if ( empty($templatePath) ){
            $templatePath = dirname(__FILE__).'/../Resources/default-template.json';
        }
        $this->config = json_decode(file_get_contents($templatePath),true);
    }

    /**
     * Get resulting configuration
     */
    public function getConfig(){
        return $this->config;
    }

    /**
     * Set homepage
     * @return $self
     */
    public function setHomepage($homepage){
        $this->config['homepage'] = $homepage;

        return $this;
    }

    /**
     * Turn on mirror mode
     * @return $self
     */
    public function enableArchive(){
        $this->config['archive'] = array(
            'directory' => 'dist',
            'format' => 'tar',
            'skip-dev' => true
        );
    }

}