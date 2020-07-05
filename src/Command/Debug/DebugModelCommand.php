<?php

namespace Ang3\Bundle\OdooBundle\Command\Debug;

use Ang3\Bundle\OdooBundle\Connection\ClientRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class DebugModelCommand extends Command
{
    public const ERROR_MODEL_NOT_FOUND = 70;

    protected static $defaultName = 'odoo:debug:model';

    private ClientRegistry $clientRegistry;

    public function __construct(ClientRegistry $clientRegistry)
    {
        parent::__construct();

        $this->clientRegistry = $clientRegistry;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Debug model and fields')
            ->addArgument('model_name', InputArgument::REQUIRED, 'Entity name')
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Connection to use', 'default')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $modelName */
        $modelName = $input->getArgument('model_name');

        /** @var bool|int|float|string $connectionName */
        $connectionName = $input->getOption('connection');
        $connectionName = (string) $connectionName;

        try {
            $client = $this->clientRegistry->get($connectionName);
            $expressionBuilder = $client->expr();
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->title(sprintf('Debugging Odoo model "%s"', $modelName));
        $io->text('<info>Loading data...</info>');
        $model = $client->findOneBy('ir.model', $expressionBuilder->eq('model', $modelName));

        if (!$model) {
            $io->error(sprintf('The model "%s" was not found on Odoo database', $modelName));

            return self::ERROR_MODEL_NOT_FOUND;
        }

        $io->text('<info>Dumping model...</info>');
        $io->newLine();

        $cliDumper = new CliDumper();
        $varCloner = new VarCloner();

        $cliDumper->dump($varCloner->cloneVar($model));
        $io->newLine();

        if ($io->confirm('Debug fields?', true)) {
            $io->text('<info>Loading Fields...</info>');
            $fields = $client->findBy('ir.model.fields', $expressionBuilder->eq('model_id', $model['id']));
            $io->text(sprintf('<info>%d field(s) loaded</info>', count($fields)));

            $fieldNames = array_combine(array_keys($fields), array_column($fields, 'name')) ?: [];

            do {
                $fieldName = $io->choice('Please select the field to debug', $fieldNames);

                $cliDumper->dump($varCloner->cloneVar($fields[array_search($fieldName, $fieldNames)]));
                $io->newLine();

                if (!$io->confirm('Debug another field?', true)) {
                    break;
                }
            } while (null !== $fieldName);
        }

        $io->success('Done');

        return 0;
    }
}
