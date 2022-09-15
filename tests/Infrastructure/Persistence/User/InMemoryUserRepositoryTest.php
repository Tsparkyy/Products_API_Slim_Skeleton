<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\User;

use App\Domain\User\Product;
use App\Domain\User\ProductNotFoundException;
use App\Infrastructure\Persistence\User\InMemoryProductRepository;
use Tests\TestCase;

class InMemoryUserRepositoryTest extends TestCase
{
    public function testFindAll()
    {
        $user = new Product(1, 'bill.gates', 'Bill', 'Gates');

        $userRepository = new InMemoryProductRepository([1 => $user]);

        $this->assertEquals([$user], $userRepository->findAll());
    }

    public function testFindAllUsersByDefault()
    {
        $users = [
            1 => new Product(1, 'bill.gates', 'Bill', 'Gates'),
            2 => new Product(2, 'steve.jobs', 'Steve', 'Jobs'),
            3 => new Product(3, 'mark.zuckerberg', 'Mark', 'Zuckerberg'),
            4 => new Product(4, 'evan.spiegel', 'Evan', 'Spiegel'),
            5 => new Product(5, 'jack.dorsey', 'Jack', 'Dorsey'),
        ];

        $userRepository = new InMemoryProductRepository();

        $this->assertEquals(array_values($users), $userRepository->findAll());
    }

    public function testFindUserOfId()
    {
        $user = new Product(1, 'bill.gates', 'Bill', 'Gates');

        $userRepository = new InMemoryProductRepository([1 => $user]);

        $this->assertEquals($user, $userRepository->findUserOfId(1));
    }

    public function testFindUserOfIdThrowsNotFoundException()
    {
        $userRepository = new InMemoryProductRepository([]);
        $this->expectException(ProductNotFoundException::class);
        $userRepository->findUserOfId(1);
    }
}
