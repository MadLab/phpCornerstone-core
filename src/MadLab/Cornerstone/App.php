<?php

namespace MadLab\Cornerstone;

use MadLab\Cornerstone\Components\Router;
use MadLab\Cornerstone\Components\SessionBridges\SessionBridgeInterface;
use MadLab\Cornerstone\Components\TemplateBridges\TemplateBridgeInterface;
use Controller;

class   App
{
    public static $instance;
    public static $error;
    public $path;
    public $environment;
    public $config;
    public $dependencies;
    public $session;
    public $template;

    public function __construct($path = false)
    {
        $this->path = $path;
        $this->environment = 'production';
        set_exception_handler(
            function (\Exception $e) {
                App::$error = true;
                if ($e->getCode() == '404') {
                    header('HTTP/1.0 404 Not Found');
                    if (is_readable('errorPages/404/Controller.php')) {
                        include 'errorPages/404/Controller.php';
                        $controller = new \ErrorController();
                        if ($controller->templateEnabled !== false && App::getInstance()->template instanceof TemplateBridgeInterface) {
                            $controller->setTemplateBridge(App::getInstance()->template);
                            $controller->view = 'errorPages/404/view';
                        }
                        $controller->get();
                        $controller->display();
                    }
                }
                else{
                    if (is_readable('errorPages/exception/Controller.php')) {
                        include 'errorPages/exception/Controller.php';
                        $controller = new \ErrorController();

                        $app = App::getInstance();
                        if ($controller->templateEnabled !== false && $app->template instanceof TemplateBridgeInterface) {
                            $controller->setTemplateBridge($app->template);
                            $controller->view = 'errorPages/exception/view';
                        }
                        $controller->set_args(array('exception'=> $e));
                        $controller->get();
                        $controller->display();
                    }
                    die();
                }
            }
        );
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


    public function run()
    {
        $route = $this->findCustomRoute();
        $this->domain = $_SERVER['HTTP_HOST'];
        $this->url = $_SERVER['REQUEST_URI'];
        $this->args = $_GET;

        if ($route) {
            $path = $route->controller;
            $this->args = $route->pathVariables;
        } else {
            $subdomainFolder = "";
            $subdomain = str_replace($this->config->get('NAKED_DOMAIN'), '', $this->domain);
            if (substr($subdomain, -1) == '.') {
                $subdomain = substr($subdomain, 0, -1);
                if ($subdomain != $this->config->get('DEFAULT_SUBDOMAIN')) {
                    $subdomainFolder = '_' . $subdomain . '_/';

                    if (!is_dir('pages/' . $subdomainFolder)) {
                        if (is_dir('pages/' . '_*_/')) {
                            $subdomainFolder = '_*_/';
                        }
                    }
                    $this->args['subdomain'] = $subdomain;
                }
            }

            $path = \MadLab\Cornerstone\Utilities\Url::convertUrlToPath($this->url);
            $path = $subdomainFolder . $path;
        }

        if (empty($path)) {
            $controllerPath = '';
        } elseif (is_dir('pages/' . $path) && is_readable('pages/' . $path . '/Controller.php')) {
            $controllerPath = $path . '/';
        } elseif (is_readable('pages/' . $path)) {
            $file = 'pages/' . $path;

            $filePathInfo = pathinfo($file);
            $fileExtention = $filePathInfo['extension'];
            switch ($fileExtention) {
                case 'css':
                    header("Content-type: text/css");
                    break;
                case 'js':
                    header("Content-type: text/javascript");
                    break;
                default:
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                        header("Content-type: " . finfo_file($finfo, $file));
                        finfo_close($finfo);
                    } elseif (function_exists('mime_content_type')) {
                        header("Content-type: " . mime_content_type($file));
                    } else {
                        header("Content-type: text/plain");
                    }
            }
            readfile($file);
            die();
        } else {
            $this->notFound();
        }

        include 'pages/' . $controllerPath . 'Controller.php';
        $controller = new Controller();
        $controller->set_args($this->args);

        $controller->session = $this->getSessionHandler();
        if ($controller->templateEnabled !== false && $this->template instanceof TemplateBridgeInterface) {
            $controller->setTemplateBridge($this->template);
            $controller->view = $controllerPath . 'view';
        }
        $controller->get();
        $controller->display();

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

    /**
     * Attempts to match the current URL to a custom route in the routes.php file
     * @return boolean|Route returns the matching Route if found, false otherwise
     */
    private function findCustomRoute()
    {
        include('routes.php');
        foreach (Router::$routes as $route) {
            if ($route->matchUrl($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])) {
                return $route;
            }
        }
        return false;
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

    public static function notFound(){
        self::$error = true;
        header('HTTP/1.0 404 Not Found');

        if (is_readable('errorPages/404/Controller.php')) {
            include 'errorPages/404/Controller.php';
            $controller = new \ErrorController();
            if ($controller->templateEnabled !== false && App::getInstance()->template instanceof TemplateBridgeInterface) {
                $controller->setTemplateBridge(App::getInstance()->template);
                $controller->view = 'errorPages/404/view';
            }
            $controller->get();
            $controller->display();
            die();
        }
    }

}

