<?php
declare(strict_types=1);

namespace App\Command;

use League\Csv\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\Utils\CsvProductImport;

class CsvImportProductCommand extends Command
{
    protected static $defaultName = 'app:csv-import-product';

    /**
     * @var CsvProductImport
     */
    private CsvProductImport $csvProductImport;


    public function __construct(
        CsvProductImport $csvProductImport
    ) {
        parent::__construct();
        $this->csvProductImport = $csvProductImport;
    }
    protected function configure(): void
    {
        $this->addArgument('csv_path', InputArgument::REQUIRED, 'Enter the name of the csv file.');
        $this
            ->addOption(
                'test',
                null,
                InputOption::VALUE_OPTIONAL,
                'Test mode. This will perform
                everything the normal import does, but not insert the data into the database.',
                0
            );
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Product Import',
            '============',
        ]);
        $csvPath = $input->getArgument('csv_path');
        $ext = pathinfo($csvPath, PATHINFO_EXTENSION);
        if ($ext !== 'csv') {
            $output->writeln($ext . ' format not found.');
            return Command::FAILURE;
        } elseif (!file_exists($csvPath)) {
            $output->writeln('File not exist - ' . $csvPath);
            return Command::FAILURE;
        }

        $testMode = $input->getOption('test') !== 0;

        $statusExecute = $this->csvProductImport->execute($csvPath, $output, $testMode);

        if (!$statusExecute || $this->csvProductImport->getRowCount() === 0) {
            $output->writeln([
                '',
                'File data is empty or invalid - ' . $csvPath
            ]);
        }

        if ($testMode) {
            $output->writeln([
                '',
                'Test Mode - no writing rows in DB',
                '============',
            ]);
        }

        $output->writeln([
            '',
            'Total processed rows: ' . $this->csvProductImport->getRowCount(),
            '============',
        ]);

        $output->writeln([
            'Total successful rows: ' . $this->csvProductImport->getRowValidCount(),
            '============',
        ]);

        $output->writeln([
            'Total error rows: ' . $this->csvProductImport->getRowErrorCount(),
            '============',
        ]);

        return Command::SUCCESS;
    }
}
