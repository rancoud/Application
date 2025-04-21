<?php

declare(strict_types=1);

namespace tests;

use Rancoud\Application\Application;

/** @internal */
class ImplementApplication extends Application
{
    public function convertMemoryLimitToBytes(string $memoryLimit): int
    {
        return parent::convertMemoryLimitToBytes($memoryLimit);
    }

    public function getMemoryPercentage(int $memoryUsage, string $memoryLimit): float
    {
        return parent::getMemoryPercentage($memoryUsage, $memoryLimit);
    }
}
