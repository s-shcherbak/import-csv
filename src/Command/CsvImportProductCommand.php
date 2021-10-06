<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use App\Model\ProductModel;
use App\Service\Utils\CsvProductImport;

class CsvImportProductCommand extends Command
{
    protected static $defaultName = 'app:csv-import-product';

    /**
     * @var ProductModel
     */
    private $product;

    /**
     * @var CsvProductImport
     */
    private $csvProductImport;


    public function __construct(
        ProductModel $product,
        CsvProductImport $csvProductImport
    )
    {
        parent::__construct();
        $this->product = $product;
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
            )
            ->addOption(
                'view-error',
                null,
                InputOption::VALUE_OPTIONAL,
                'View 100 lines error in csv',
                0
            );
    }

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
        $resultRows = $this->csvProductImport->execute($csvPath);

        if (count($resultRows) === 0) {
            $output->writeln('File data is empty or invalid - ' . $csvPath);
        }

        if ($input->getOption('test') === 0 && count($resultRows) > 0) {
            $output->writeln([
                'Writing to DB',
            ]);
            [   $countInsertProduct,
                $countRewriteProduct,
                $countErrorProduct
            ] = $this->product->addProducts($resultRows, $output);

            $this->csvProductImport->updateRowCountByDB($countInsertProduct, $countErrorProduct);

            $output->writeln([
                '',
            ]);
        } else {
            $output->writeln([
                'Test Mode - no writing rows in DB',
                '============',
            ]);
        }

        $output->writeln([
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

        if ($input->getOption('view-error') !== 0 && $this->csvProductImport->getRowErrorCount() > 0) {
            $output->writeln([
                'Error rows (first 100 lines): ',
                '============',
            ]);
            $table = new Table($output);
            $table
                ->setHeaders(['line_id'] + $this->csvProductImport->getHeaderFile())
                ->setRows(array_slice($this->csvProductImport->getRowsError(), 0, 100));
            $table->render();
        }

        return Command::SUCCESS;
    }
}
