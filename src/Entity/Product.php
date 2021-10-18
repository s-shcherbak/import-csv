<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Product
 *
 * @ORM\Table(name="tblProductData", uniqueConstraints={@ORM\UniqueConstraint(name="strProductCode", columns={"strProductCode"})})
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 */
class Product
{
    public const DISCONTINUED_YES = 'yes';

    /**
     * @var int
     *
     * @ORM\Column(name="intProductDataId", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @var string
     * @Assert\Length(
     *      min = 1,
     *      max = 50,
     *      minMessage = "Product name must be at least {{ limit }} characters long",
     *      maxMessage = "Product name cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(name="strProductName", type="string", length=50, nullable=false)
     */
    private string $name;

    /**
     * @var string
     * @Assert\Length(
     *      min = 1,
     *      max = 255,
     *      minMessage = "Product description must be at least {{ limit }} characters long",
     *      maxMessage = "Product description cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(name="strProductDesc", type="string", length=255, nullable=false)
     */
    private string $description;

    /**
     * @var string
     * @Assert\Length(
     *      min = 1,
     *      max = 10,
     *      minMessage = "Product code must be at least {{ limit }} characters long",
     *      maxMessage = "Product code cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(name="strProductCode", type="string", length=10, nullable=false)
     */
    private string $code;

    private ?string $discontinued = null;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="dtmAdded", type="datetime", nullable=true)
     */
    private ?\DateTime $dateAdded;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="dtmDiscontinued", type="datetime", nullable=true)
     */
    private ?\DateTime $dateDiscontinued;

    /**
     * @var \DateTime
     * @ORM\Column(name="stmTimestamp", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private \DateTime $timestamp;

    /**
     * @var int
     *
     * @ORM\Column(name="intStock", type="integer", nullable=false)
     */
    private int $stock;

    /**
     * @var float
     *
     * @ORM\Column(name="dcmlPrice", type="decimal", precision=11, scale=2, nullable=false)
     */
    private float $price;

    public function __construct(
        string $code,
        string $name,
        string $description,
        int $stock,
        float $price,
        ?string $discontinued
    ) {
        $nowDateTime = new \DateTime("now");
        $this->code = $code;
        $this->name = $name;
        $this->description = $description;
        $this->stock = $stock;
        $this->price = $price;
        $this->dateAdded = $nowDateTime;
        $this->timestamp = $nowDateTime;
        $this->discontinued = $discontinued;

        if ($this->isDiscontinued($discontinued)) {
            $this->dateDiscontinued = $nowDateTime;
        }
    }

    /**
     * @param string|null $discontinued
     *
     * @return bool
     */
    protected function isDiscontinued(?string $discontinued): bool
    {
        return $discontinued === self::DISCONTINUED_YES;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getDateAdded(): ?\DateTimeInterface
    {
        return $this->dateAdded;
    }

    public function getDateDiscontinued(): ?\DateTimeInterface
    {
        return $this->dateDiscontinued;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function getDiscontinued(): ?string
    {
        return $this->discontinued;
    }
}
