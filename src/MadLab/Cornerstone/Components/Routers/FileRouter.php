<?php

namespace MadLab\Cornerstone\Components\Routers;

use \MadLab\Cornerstone\Utilities\Url;

class FileRouter
{

    private $domain;

    public function __construct($domain, $defaultSubdomain){
        $this->domain = $domain;
        $this->defaultSubdomain = $defaultSubdomain;
        $this->variables = array();
    }

    public function findRoute(){
        $subdomainFolder = "";
        $subdomain = str_replace($this->domain, '', $_SERVER['HTTP_HOST']);
        if (substr($subdomain, -1) == '.') {
            $subdomain = substr($subdomain, 0, -1);
            if ($subdomain != $this->defaultSubdomain) {
                $subdomainFolder = '_' . $subdomain . '_/';
                if (!is_dir('pages/' . $subdomainFolder)) {
                    if (is_dir('pages/' . '_*_/')) {
                        $subdomainFolder = '_*_/';
                    }
                }
                $this->variables['subdomain'] = $subdomain;
            }
        }

        $path = Url::convertUrlToPath($_SERVER['REQUEST_URI']);
        $path = $subdomainFolder . $path;


        if (empty($path)) {
            $this->controllerPath = '';
        } elseif (is_dir('pages/' . $path) && is_readable('pages/' . $path . '/Controller.php')) {
            $this->controllerPath = $path . '/';
        } elseif (is_readable('pages/' . $path) && is_file('pages/' . $path)) {
            $this->type = 'file';
            $file = 'pages/' . $path;

            $filePathInfo = pathinfo($file);
            if(isset($filePathInfo['extension'])){
                $fileExtension = $filePathInfo['extension'];
            }
            else $fileExtension = false;
            switch ($fileExtension) {
                case 'css':
                    $this->header = "Content-type: text/css";
                    break;
                case 'js':
                    $this->header = "Content-type: text/javascript";
                    break;
                case 'svg':
                    $this->header = "Content-Type: image/svg+xml";
                    break;
                default:
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                        $this->header = "Content-type: " . finfo_file($finfo, $file);
                        finfo_close($finfo);
                    } elseif (function_exists('mime_content_type')) {
                        $this->header = "Content-type: " . mime_content_type($file);
                    } else {
                        $this->header = "Content-type: text/plain";
                    }
            }
            $this->file = $file;
        } else {
            return false;
        }
        return true;
    }

    public function getDetails(){
        $return = new \StdClass();
        if(isset($this->type) && $this->type == 'file'){
            $return->type = 'file';
            $return->header = $this->header;
            $return->file = $this->file;
        }
        else{
            $return->type = 'controller';
            $return->controller = $this->controllerPath;
            $return->variables = $this->variables;
        }
        return $return;
    }

}