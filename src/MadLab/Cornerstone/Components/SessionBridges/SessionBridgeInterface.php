<?php

namespace MadLab\Cornerstone\Components\SessionBridges;

interface SessionBridgeInterface{

    public static function set($key, $value);
    public static function get($key);
    public static function has($key);
    public static function migrate();
    public static function destroy();
}