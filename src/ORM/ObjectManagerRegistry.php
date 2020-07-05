<?php

namespace Ang3\Bundle\OdooBundle\ORM;

use Ang3\Component\Odoo\ORM\ObjectManager;
use RuntimeException;

class ObjectManagerRegistry
{
    /**
     * @var array<string, ObjectManager>
     */
    private array $managers = [];

    public function add(string $connectionName, ObjectManager $objectManager): void
    {
        if ($this->has($connectionName)) {
            throw new RuntimeException(sprintf('The Odoo ORM manager "%s" is already registered', $connectionName));
        }

        $this->managers[$connectionName] = $objectManager;
    }

    public function get(string $connectionName): ObjectManager
    {
        if (!$this->has($connectionName)) {
            throw new RuntimeException(sprintf('The Odoo ORM manager "%s" was not found', $connectionName));
        }

        return $this->managers[$connectionName];
    }

    public function has(string $connectionName): bool
    {
        return array_key_exists($connectionName, $this->managers);
    }
}
