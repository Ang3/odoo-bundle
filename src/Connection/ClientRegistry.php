<?php

namespace Ang3\Bundle\OdooBundle\Connection;

use Ang3\Component\Odoo\Client;
use RuntimeException;

class ClientRegistry
{
    /**
     * @var array
     */
    private $registry = [];

    public function add(string $connectionName, Client $client): void
    {
        if ($this->has($connectionName)) {
            throw new RuntimeException(sprintf('The Odoo connection "%s" is already registered', $connectionName));
        }

        $this->registry[$connectionName] = $client;
    }

    /**
     * @throws RuntimeException when the connection was not found
     */
    public function get(string $connectionName): Client
    {
        if (!$this->has($connectionName)) {
            throw new RuntimeException(sprintf('The Odoo connection "%s" was not found', $connectionName));
        }

        return $this->registry[$connectionName];
    }

    public function has(string $connectionName): bool
    {
        return array_key_exists($connectionName, $this->registry);
    }
}
