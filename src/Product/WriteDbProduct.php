<?php
declare(strict_types=1);

namespace App\Product;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WriteDbProduct
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var SerializeProduct
     */
    private $serialize;

    private int $dbWriterBatch;

    public function __construct(
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        SerializeProduct  $serialize,
        ParameterBagInterface $params
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->serialize = $serialize;
        $this->dbWriterBatch = $params->get('db_writer_batch');

    }

    public function addProducts(array $productsRowJson): int
    {
        $countSuccessProduct = 0;
        $countErrorProduct = 0;

        /**  if --no-debug off, cache doctrine off **/
        $sqlLogger = $this->em->getConnection()->getConfiguration()->getSQLLogger();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $isStartTransaction = true;
        /** set settings for denormalize array to object */
        $this->serialize->setDenormalizeSettings();

        foreach ($productsRowJson as $code => $productRowJson) {
            if ($isStartTransaction) {
                $this->em->getConnection()->beginTransaction();
                $isStartTransaction = false;
            }
            try {
                $product = $this->em->getRepository(Product::class)->findOneBy(['code' => $code]);
                /** if product not exist then insert else update **/
                if ($product === null) {
                    $product = new Product();
                }

                /* @var $product Product */
                $product = $this->serialize->getDenormalizeProduct(
                    $productRowJson,
                    $product
                );

                $errors = $this->validator->validate($product);

                if ($errors->count() > 0) {
                    $countErrorProduct++;
                    $this->em->detach($product);
                } else {
                    $countSuccessProduct++;
                    $this->em->persist($product);
                }

                /** batch commit to db or last iteration array **/
                if (($countSuccessProduct % $this->dbWriterBatch) == 0 || array_key_last($productsRowJson) === $code) {
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

        $this->em->getConnection()->getConfiguration()->setSQLLogger($sqlLogger);

        return $countErrorProduct;
    }
}