<?php
declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\CsvImportProductCommand;
use App\Product\SerializeProduct;
use App\Product\WriteDbProduct;
use App\Product\Csv\ProductImport;
use App\Service\Utils\CsvProductImport;
use App\Tests\BaseTest;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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

        $csvReaderBatch = 1000;
        $dbWriterBatch = 100;

        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $validator = Validation::createValidator();

        $app = new Application($kernel);
        $parameterBagImportInterface = $this->createMock(ParameterBagInterface::class);
        $parameterBagImportInterface->expects($this->once())
            ->method('get')
            ->willReturn($csvReaderBatch);
        $parameterBagDBInterface = $this->createMock(ParameterBagInterface::class);
        $parameterBagDBInterface->expects($this->once())
            ->method('get')
            ->willReturn($dbWriterBatch);
        $serialize = new SerializeProduct();
        $writeDbProduct = new WriteDbProduct($entityManager, $validator, $serialize, $parameterBagDBInterface);
        $productImport = new ProductImport($serialize, $validator);
        $csvProductImport = new CsvProductImport($parameterBagImportInterface, $productImport, $writeDbProduct);
        $app->add(new CsvImportProductCommand($csvProductImport, 'upload/csv'));
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