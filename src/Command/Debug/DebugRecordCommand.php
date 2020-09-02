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

class DebugRecordCommand extends Command
{
    public const ERROR_MODEL_NOT_FOUND = 70;
    public const ERROR_RECORD_NOT_FOUND = 71;

    protected static $defaultName = 'odoo:debug:record';

    private $clientRegistry;

    public function __construct(ClientRegistry $clientRegistry)
    {
        parent::__construct();

        $this->clientRegistry = $clientRegistry;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Debug record by model name and id')
            ->addArgument('model_name', InputArgument::REQUIRED, 'Entity name')
            ->addArgument('record_id', InputArgument::REQUIRED, 'Record ID')
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Connection to use', 'default')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $modelName */
        $modelName = $input->getArgument('model_name');

        /** @var bool|int|float|string $recordId */
        $recordId = $input->getArgument('record_id');

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

        $io->title(sprintf('Debugging record of id %d from model "%s"', $recordId, $modelName));
        $io->text('<info>Checking record...</info>');

        if (0 === $client->count('ir.model', $expressionBuilder->eq('model', $modelName))) {
            $io->error(sprintf('The model "%s" was not found on Odoo database', $modelName));

            return self::ERROR_MODEL_NOT_FOUND;
        }

        $io->text('<info>Loading record...</info>');
        $record = $client->find($modelName, (int) $recordId);

        if (null === $record) {
            $io->error(sprintf('The record "%s" #%d was not found on Odoo database', $modelName, $recordId));

            return self::ERROR_RECORD_NOT_FOUND;
        }

        $io->text('<info>Dumping record...</info>');
        $io->newLine();

        $cliDumper = new CliDumper();
        $varCloner = new VarCloner();

        ksort($record);
        $cliDumper->dump($varCloner->cloneVar($record));
        $io->newLine();

        $io->success('Done');

        return 0;
    }
}
