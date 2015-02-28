<?php

namespace MadLab\Cornerstone;

use Carbon\Carbon;
use MadLab\Cornerstone\Components\Router;
use MadLab\Cornerstone\Components\SessionBridges\SessionBridgeInterface;
use MadLab\Cornerstone\Components\TemplateBridges\TemplateBridgeInterface;
use Controller;

class   App
{
    public static $instance;
    public static $error;
    public static $appStartTime;
    public $path;
    public $environment;
    public $config;
    public $dependencies;
    public $session;
    public $template;
    private $routers;


    public function __construct($path = false)
    {
        self::$appStartTime = microtime(true);
        $this->path = $path;
        $this->environment = 'production';
        $this->routers = array();
        $this->loadConfig();
    }

    /**
     * Framework instance factory. Will return the active framework instance, or create one if it doesn't exist
     * @static getInstance
     * @return App Instance object
     */
    public static function getInstance($path = false)
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c($path);
        }
        return self::$instance;
    }

    public static function addRouter($router){
        self::$instance->routers[] = $router;
    }


    public function run()
    {
        include('routes.php');


        foreach($this->routers as $router){
            if($router->findRoute()){
                $this->displayRoute($router->getDetails());
                exit();
            }
        }



        $this->notFound();

    }

    public function displayRoute($routeDetails){
        $type = $routeDetails->type;
        $controllerPath = false;
        $args = array();
        $type = $routeDetails->type;
        if(isset($routeDetails->controller)){
            $controllerPath = $routeDetails->controller;
        }
        if(isset($routeDetails->variables)){
            $args = $routeDetails->variables;
        }


        if($type == 'controller'){
            include 'pages/' . $controllerPath . 'Controller.php';
            $controller = new Controller();
            $controller->set_args($args);
            $controller->session = $this->getSessionHandler();
            if ($controller->templateEnabled !== false && $this->template instanceof TemplateBridgeInterface) {
                $controller->setTemplateBridge($this->template);
                $controller->view = $controllerPath . 'view';
            }

            $controller->get();
            $controller->display();
        }
        elseif($type == 'file'){
            header($routeDetails->header);
            readfile($routeDetails->file);
        }
    }

    public function detectEnvironment($environments = array())
    {
        foreach ($environments as $name => $environment) {
            if (substr($_SERVER['SERVER_NAME'], -strlen($environment)) === $environment) {
                $this->environment = $name;
                $this->config->loadDirectory($this->path . "/config/" . $this->environment);
                break;
            }
        }
    }

    private function loadConfig()
    {

        $this->config = new Components\Config();
        $this->config->loadDirectory($this->path . "/config");

    }

    public function addDependency($name, $object)
    {
        $this->dependencies[$name] = $object;
    }

    public function resolve($dependency)
    {
        return $this->dependencies[$dependency];
    }

    public function setSessionHandler(SessionBridgeInterface $session)
    {
        $this->session = $session;
    }

    public function getSessionHandler()
    {
        if ($this->session instanceof SessionBridgeInterface) {
            return $this->session;
        } else {
            return false;
        }
    }

    public function setTemplateHandler(TemplateBridgeInterface $template)
    {
        $this->template = $template;
    }

    public static function redirect($location, $status = false)
    {
        if ($status == '301') {
            header("HTTP/1.1 301 Moved Permanently");
        } elseif ($status == '302') {
            header("HTTP/1.1 302 Moved Temporarily");
        }
        header('Location: ' . $location);
        die();
    }

    public static function executionTime()
    {

        return round(microtime(true)- self::$appStartTime,2);
    }

    public static function notFound()
    {
        self::$error = true;
        header('HTTP/1.0 404 Not Found');

        if (is_readable('errorPages/404/Controller.php')) {
            include 'errorPages/404/Controller.php';
            $controller = new \ErrorController();
            if ($controller->templateEnabled !== false && App::getInstance(
                )->template instanceof TemplateBridgeInterface
            ) {
                $controller->setTemplateBridge(App::getInstance()->template);
                $controller->view = 'errorPages/404/view';
            }
            $controller->get();
            $controller->display();
            die();
        }
        else{
            echo "404 Not Found";
        }
    }

}

