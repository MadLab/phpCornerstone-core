<?php

namespace MadLab\Cornerstone\Components;

class Config
{

    private static $_data;

    /**
     * Get value from Config Key/Value Store
     * @static get
     *
     * @param string $key The Key to retrieve
     *
     * @return null|String The value
     */
    public static function get($key, $default = null)
    {
        if (array_key_exists($key, self::$_data)) {
            return self::$_data[$key];
        } else{
            return $default;
        }
    }

    /**
     * Store a value in the Config Key/Value store
     * @static set
     *
     * @param string $key The key to store the value in
     * @param string $value The value to store
     */
    public static function set($key, $value)
    {
        self::$_data[$key] = $value;
    }

    public function __get($key){
        return $this->get($key);
    }

    public function __set($key, $value){
        return $this->set($key, $value);
    }

    public function loadDirectory($directory){
        if(!is_readable($directory)){
            throw new \Exception("Config Directory Unreadable");
        }

        $files = scandir($directory);

        foreach($files as $file){
            if($file == '.' || $file == '..'){
                continue;
            }
            $fileParts = pathinfo($file);
            if(array_key_exists('extension', $fileParts) && $fileParts['extension'] == 'php'){
                $this->loadFile($directory . '/' . $file);
            }
        }
    }

    public function loadFile($file){
        if(is_readable($file)){
            include($file);
        }
        else throw new \Exception("Config File Unreadable: $file");
    }
}