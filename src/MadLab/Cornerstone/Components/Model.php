<?php

namespace MadLab\Cornerstone\Components;

use MadLab\Cornerstone\App;

class Model
{

    public function resolve($dependency){
        return App::getInstance()->resolve($dependency);
    }
}