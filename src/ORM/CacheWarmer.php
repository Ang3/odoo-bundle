<?php

namespace Ang3\Bundle\OdooBundle\ORM;

use RuntimeException;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class CacheWarmer implements CacheWarmerInterface
{
    private ObjectManagerRegistry $objectManagerRegistry;
    private array $config;

    public function __construct(ObjectManagerRegistry $objectManagerRegistry, array $config = [])
    {
        $this->objectManagerRegistry = $objectManagerRegistry;
        $this->config = $config;
    }

    /**
     * @param string $cacheDir
     *
     * @throws RuntimeException on cache failure
     */
    public function warmUp($cacheDir): array
    {
        $managers = $this->config['managers'] ?? [];

        foreach ($managers as $managerName => $managerConfig) {
            $paths = $managerConfig['paths'] ?? [];

            $this->objectManagerRegistry
                ->get($managerName)
                ->getClassMetadataFactory()
                ->getMetadataLoader()
                ->load($paths);
        }

        return [];
    }

    public function isOptional()
    {
        return true;
    }
}
