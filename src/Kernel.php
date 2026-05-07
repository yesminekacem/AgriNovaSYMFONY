<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getCacheDir(): string
    {
        $cacheDir = getenv('APP_CACHE_DIR');
        if (is_string($cacheDir) && $cacheDir !== '') {
            return $cacheDir;
        }

        if (array_key_exists('APP_CACHE_DIR', $_ENV) && is_string($_ENV['APP_CACHE_DIR']) && $_ENV['APP_CACHE_DIR'] !== '') {
            return $_ENV['APP_CACHE_DIR'];
        }

        return parent::getCacheDir();
    }

    public function getBuildDir(): string
    {
        $buildDir = getenv('APP_BUILD_DIR');
        if (is_string($buildDir) && $buildDir !== '') {
            return $buildDir;
        }

        if (array_key_exists('APP_BUILD_DIR', $_ENV) && is_string($_ENV['APP_BUILD_DIR']) && $_ENV['APP_BUILD_DIR'] !== '') {
            return $_ENV['APP_BUILD_DIR'];
        }

        return $this->getCacheDir();
    }
}
