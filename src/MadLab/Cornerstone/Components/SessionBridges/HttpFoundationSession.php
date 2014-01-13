<?php

namespace MadLab\Cornerstone\Components\SessionBridges;

use Symfony\Component\HttpFoundation\Session\Session;

class HttpFoundationSession implements SessionBridgeInterface{

    public static $session;

    public function __construct($session){
        self::$session = $session;
    }

    public static function set($key, $value){
        return self::$session->set($key, $value);
    }

    public static function get($key){
        return self::$session->get($key);
    }

    public static function has($key){
        $result = self::get($key);
        if($result){
            return true;
        }
        return false;
    }

    public static function migrate(){
        return self::$session->migrate();
    }

    public static function clear(){
        return self::$session->clear();
    }

    public static function destroy(){
        self::clear();
        self::migrate();
        //return self::$session->invalidate();
    }
}
