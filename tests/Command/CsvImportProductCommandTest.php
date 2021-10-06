<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\CsvImportProductCommand;
use App\Model\ProductModel;
use App\Service\Utils\CsvProductImport;
use App\Tests\BaseTest;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Validator\Validation;

/**
 * Class ImportCsvCommandTest
 * @package Tests\ImportBundle\Command
 */
class CsvImportProductCommandTest extends BaseTest
{
    private $commandTester;

    public function setUp():void
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $validator = Validation::createValidator();

        $app = new Application($kernel);
        $productModel = new ProductModel($entityManager, $validator);
        $csvProductImport = new CsvProductImport();
        $app->add(new CsvImportProductCommand($productModel, $csvProductImport, 'upload/csv'));
        $command = $app->find('app:csv-import-product');
        $this->commandTester = new CommandTester($command);
    }

    /**
     * Invalid File format
     */
    public function testExecuteWithBadFormat(): void
    {
        $this->commandTester->execute(
            array(
                'csv_path' => __DIR__ . '/../Fixtures/stock.csvv'
            )
        );

        $this->assertMatchesRegularExpression('/csvv format not found/', $this->commandTester->getDisplay());

    }

    /**
     * Testing how command execute with file not exist
     */
    public function testExecuteWithBadFile(): void
    {
        $csvPath = __DIR__. '/../Fixtures/stock1.csv';
        $this->commandTester->execute(
            array(
                'csv_path' => $csvPath
            )
        );
        $this->assertMatchesRegularExpression('/File not exist/', $this->commandTester->getDisplay());

    }

    /**
     * Testing how command execute with empty file
     */
    public function testExecuteWithEmptyFile(): void
    {
        $csvPath = __DIR__. '/../Fixtures/stockEmpty.csv';
        $this->commandTester->execute(
            array(
                'csv_path' => $csvPath
            )
        );
        $this->assertMatchesRegularExpression('/File data is empty or invalid/', $this->commandTester->getDisplay());
    }

    /**
     * Testing how command execute with header but not data file
     */
    public function testExecuteEmptyWithHeaderFile(): void
    {
        $csvPath = __DIR__. '/../Fixtures/stockEmptyWithHeader.csv';
        $this->commandTester->execute(
            array(
                'csv_path' => $csvPath
            )
        );
        $this->assertMatchesRegularExpression('/File data is empty or invalid/', $this->commandTester->getDisplay());
    }

    /**
     * Testing how command execute with invalid header file
     */
    public function testExecuteWithInvalidFileHeader(): void
    {
        $csvPath = __DIR__. '/../Fixtures/stockHeaderInvalid.csv';
        $this->commandTester->execute(
            array(
                'csv_path' => $csvPath
            )
        );
        $this->assertMatchesRegularExpression('/File data is empty or invalid/', $this->commandTester->getDisplay());
    }

    /**
     * Testing how command execute with valid format and file
     */
    public function testExecuteWithTest(): void
    {
        $this->commandTester->execute(
            array(
                'csv_path' => __DIR__. '/../Fixtures/stock.csv',
                '--test'  => true,
            )
        );

        $this->assertMatchesRegularExpression('/Test Mode/', $this->commandTester->getDisplay());
    }
}