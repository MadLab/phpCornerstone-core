<?php

namespace MadLab\Cornerstone;
use MadLab\Cornerstone\Pages\Controller;

class App
{
    public static $instance;
    public $path;
    public $config;
    public $dependencies;

    public function __construct($path = false)
    {
        $this->path = $path;
        $this->loadConfig();

    }

    /**
     * Framework instance factory. Will return the active framework instance, or create one if it doesn't exist
     * @static getInstance
     * @return cs Instance object
     */
    public static function getInstance($path = false)
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c($path);
        }
        return self::$instance;
    }



    public function run()
    {
        $this->domain = $_SERVER['HTTP_HOST'];
        $this->url = $_SERVER['REQUEST_URI'];
        $this->args = $_GET;

        $subdomain = str_replace($this->config->NAKED_DOMAIN, '', $this->domain);
        if (substr($subdomain, -1) == '.') {
            $subdomain = substr($subdomain, 0, -1);
        }
        $subdomainFolder = "";

        $path = \MadLab\Cornerstone\Utilities\Url::convertUrlToPath($this->url);

        if (empty($path)) {

            $controllerPath  = 'pages/';
            include $controllerPath . 'Controller.php';
            $controller = new \MadLab\Cornerstone\Pages\Controller();

            //add pre controller hook which adds dependency?
            if($this->dependencies['templateBridge']){
                $controller->setTemplateBridge($this->dependencies['templateBridge']);
            }
            $controller->get();
        } elseif (is_dir('pages/' . $subdomainFolder . $path) && is_readable('pages/' . $subdomainFolder . $path . '/controller.php')) {
            $controllerPath  = 'pages/' . $subdomainFolder . $path . '/';

            include $controllerPath . 'Controller.php';
            $controller = new \MadLab\Cornerstone\Pages\Controller();
            if($this->dependencies['templateBridge']){
                $controller->setTemplateBridge($this->dependencies['templateBridge']);
            }
            $controller->get();

        }else {
            throw new \Exception("404 Not Found");
        }
    }

    private function loadConfig(){
        $this->config = new Components\Config();
        $this->config->loadDirectory($this->path . "/config");
    }

    public function addDependency($name, $object){
        $this->dependencies[$name] = $object;
    }
}

