<?php
declare(strict_types=1);

namespace App\Domain\Product;


interface ProductRepository
{
    /**
     * @return Product[]
     */
    public function findAll(): array;

    /**
     * @param int $id
     * @return Product
     * @throws ProductNotFoundException
     */
    public function findProductOfId(string $id): object;
}
