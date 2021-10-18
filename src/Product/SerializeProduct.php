<?php
declare(strict_types=1);

namespace App\Product;

use App\Entity\Product;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SerializeProduct
{
    public Serializer $serialize;

    public function __construct()
    {
        $this->serialize = new Serializer();
    }

    private function setSerialize(array $normalizers, array $encoders) : void
    {
        $this->serialize = new Serializer($normalizers, $encoders);
    }

    /**
     * @throws ExceptionInterface
     */
    public function getNormalizeProduct(Product $product): array
    {
        return $this->serialize->normalize($product, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);
    }

    /**
     * @throws ExceptionInterface
     */
    public function getDenormalizeProduct(array $productRowJson, ?Product $product): Product
    {
        return $this->serialize->denormalize(
            $productRowJson,
            Product::class,
            'json',
            $product !== null ?  ['object_to_populate' => $product] : []
        );
    }

    public function setSerializeSettings(): void
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::RFC3339]),
            new ObjectNormalizer()
        ];
        $this->setSerialize($normalizers, $encoders);
    }

    public function setDenormalizeSettings(): void
    {
        $normalizers = array(
            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::RFC3339,
            ]),
            new ObjectNormalizer(
                null,
                null,
                null,
                new ReflectionExtractor()
            ),
        );
        $this->setSerialize($normalizers, []);
    }
}
