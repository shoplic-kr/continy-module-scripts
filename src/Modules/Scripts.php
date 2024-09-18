<?php

namespace ShoplicKr\Continy\Modules\Modules;

use ShoplicKr\Continy\Contract\Module;

class Scripts implements Module
{
    public function __construct(string $configPath = '')
    {
        if ($configPath) {
            $this->loadConfig($configPath);
        }
    }
}
