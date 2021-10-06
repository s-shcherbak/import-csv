<?php

namespace App\Model;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class ProductModel
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ) {
        $this->em = $em;
        $this->validator = $validator;

    }

    public function addProducts(array $productsRow, OutputInterface $output): array
    {
        $countSuccessProduct = 0;
        $countRewriteProduct = 0;
        $countErrorProduct = 0;
        $nowDateTime = new \DateTime("now");
        $batchSize = 100;
        $isStartTransaction = true;

        /**  if --no-debug off, cache doctrine off **/
        $sqlLogger = $this->em->getConnection()->getConfiguration()->getSQLLogger();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        /** progress bar for console **/
        $progressBar = new ProgressBar($output, count($productsRow));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        foreach ($productsRow as $key => $productRow) {
            $progressBar->advance();
            if ($isStartTransaction) {
                $this->em->getConnection()->beginTransaction();
                $isStartTransaction = false;
            }
            try {
                $product = $this->em->getRepository(Product::class)->findOneBy(['code' => $productRow['code']]);
                /** if product exist then update else insert **/
                if ($product === null) {
                    $product = new Product();
                } else {
                    $countRewriteProduct++;
                }

                $product->setCode($productRow['code'])
                    ->setName($productRow['name'])
                    ->setDescription($productRow['description'])
                    ->setStock($productRow['stock'])
                    ->setPrice($productRow['price'])
                    ->setDateAdded($nowDateTime);

                if ($productRow['discontinued']) {
                    $product->setDateDiscontinued($nowDateTime);
                }

                $errors = $this->validator->validate($product);

                if ($errors->count() > 0) {
                    $countErrorProduct++;
                    continue;
                }

                $countSuccessProduct++;

                $this->em->persist($product);

                /** batch insert commit and run last element array **/
                if (($countSuccessProduct % $batchSize) == 0 || array_key_last($productsRow) === $key) {
                    $this->em->flush();
                    $this->em->getConnection()->commit();
                    $this->em->clear();
                    $isStartTransaction = true;
                }
            } catch (\Exception $e) {
                $this->em->getConnection()->rollback();
                $this->em->close();

                throw new \RuntimeException($e->getMessage());
            }
        }

        $progressBar->finish();
        $this->em->close();
        $this->em->getConnection()->getConfiguration()->setSQLLogger($sqlLogger);

        return [
            $countSuccessProduct,
            $countRewriteProduct,
            $countErrorProduct
        ];
    }
}