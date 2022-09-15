<?php
declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository
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
    public function findUserOfId(int $id): Product;
}
