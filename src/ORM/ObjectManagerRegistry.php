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

    public function add(string $name, ObjectManager $objectManager): void
    {
        if ($this->has($name)) {
            throw new RuntimeException(sprintf('The Odoo ORM manager "%s" is already registered', $name));
        }

        $this->managers[$name] = $objectManager;
    }

    public function get(string $name): ObjectManager
    {
        if (!$this->has($name)) {
            throw new RuntimeException(sprintf('The Odoo ORM manager "%s" was not found', $name));
        }

        return $this->managers[$name];
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->managers);
    }
}
