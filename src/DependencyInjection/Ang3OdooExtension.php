<?php

namespace Ang3\Bundle\OdooBundle\DependencyInjection;

use Ang3\Bundle\OdooBundle\Connection\ClientRegistry;
use Ang3\Bundle\OdooBundle\ORM\ObjectManagerRegistry;
use Ang3\Component\Odoo\Client;
use Ang3\Component\Odoo\ORM\Configuration as OrmConfiguration;
use Ang3\Component\Odoo\ORM\ObjectManager;
use Doctrine\Common\Annotations\Reader;
use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Joanis ROUANET
 */
class Ang3OdooExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception on services file loading failure
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('ang3_odoo.parameters', $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $this->loadOdooConfiguration($container, $config);
    }

    /**
     * Load clients instances from connections params.
     */
    public function loadOdooConfiguration(ContainerBuilder $container, array $config): void
    {
        if (!array_key_exists($config['default_connection'], $config['connections'])) {
            throw new InvalidArgumentException(sprintf('The default Odoo connection "%s" is not configured', $config['default_connection']));
        }

        $connections = $config['connections'] ?? [];
        $clientRegistry = $container->getDefinition(ClientRegistry::class);

        foreach ($connections as $connectionName => $params) {
            $loggerServiceName = $params['logger'] ?: $config['default_logger'];
            $logger = $loggerServiceName ? new Reference($loggerServiceName) : null;

            $client = new Definition(Client::class, [
                $params['url'],
                $params['database'],
                $params['user'],
                $params['password'],
                $logger,
            ]);

            $clientName = $this->formatClientServiceName($connectionName);
            $container->setDefinition($clientName, $client);
            $container->registerAliasForArgument($clientName, Client::class, "$connectionName.client");

            if ($connectionName === $config['default_connection']) {
                $container->setDefinition(Client::class, $client);
                $container->setAlias('ang3_odoo.client', $clientName);
                $container->registerAliasForArgument($clientName, Client::class, 'client');
            }

            $clientReference = new Reference($clientName);
            $clientRegistry->addMethodCall('add', [$connectionName, $clientReference]);
        }

        $ormConfig = $config['orm'] ?? [];
        $ormEnabled = $ormConfig['enabled'] ?? false;

        if ($ormEnabled) {
            $this->loadOdooOrm($container, $connections, $config['default_connection'], $ormConfig);
        }
    }

    public function loadOdooOrm(ContainerBuilder $container, array $connections, string $defaultConnection, array $config): void
    {
        $managers = $config['managers'] ?? [];
        $objectManagerRegistry = $container->getDefinition(ObjectManagerRegistry::class);
        $appCache = $container->hasDefinition('cache.app') ? new Reference('cache.app') : null;

        foreach ($managers as $connectionName => $managerConfig) {
            if (!isset($connections[$connectionName])) {
                throw new InvalidArgumentException(sprintf('The Odoo connection "%s" was not found', $connectionName));
            }

            $objectManagerServiceName = sprintf('ang3_odoo.orm.object_manager.%s', $connectionName);
            $configurationServiceName = sprintf('%s.configuration', $objectManagerServiceName);
            $configuration = new Definition(OrmConfiguration::class, [
                $appCache,
                $appCache,
            ]);
            $container->setDefinition($configurationServiceName, $configuration);

            $objectManagerServiceName = sprintf('ang3_odoo.orm.object_manager.%s', $connectionName);
            $objectManager = new Definition(ObjectManager::class, [
                new Reference($this->formatClientServiceName($connectionName)),
                new Reference($configurationServiceName),
                new Reference(Reader::class),
            ]);
            $container->setDefinition($objectManagerServiceName, $objectManager);
            $container->registerAliasForArgument($objectManagerServiceName, ObjectManager::class, sprintf('%sObjectManager', $connectionName));

            if ($connectionName === $defaultConnection) {
                $container->setDefinition(ObjectManager::class, $objectManager);
                $container->setAlias('ang3_odoo.default_object_manager', $objectManagerServiceName);
            }

            $objectManagerReference = new Reference($objectManagerServiceName);
            $objectManagerRegistry->addMethodCall('add', [$connectionName, $objectManagerReference]);
        }
    }

    private function formatClientServiceName(string $connectionName): string
    {
        return sprintf('ang3_odoo.client.%s', $connectionName);
    }
}
