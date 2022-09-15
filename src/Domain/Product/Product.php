<?php
declare(strict_types=1);

namespace App\Domain\Product;

use JsonSerializable;

class Product implements JsonSerializable
{
    /**
     * @var int|null
     */
    private $id;


    /**
     * @var array
     */
    private $product;

    /**
     * @param int|null  $id
     */
    public function __construct(?int $id, object $product)
    {
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
        ];
    }
}
