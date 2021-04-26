<?php

declare(strict_types=1);

namespace tests;

use Rancoud\Application\Application;

class ImplementApplication extends Application
{
    public function convertMemoryLimitToBytes($memoryLimit)
    {
        return parent::convertMemoryLimitToBytes($memoryLimit);
    }
}
