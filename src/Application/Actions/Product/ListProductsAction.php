<?php
declare(strict_types=1);

namespace App\Application\Actions\Product;

use App\Infrastructure\Persistence\Product\InMemoryProductRepository;
use Psr\Http\Message\ResponseInterface as Response;


class ListProductsAction extends ProductAction
{
    /**
     * {@inheritdoc}
     */

    protected function action(): Response
    {
        $products = $this->productRepository->findAll();
        $this->logger->info("Products list was viewed.");
        $rep = new InMemoryProductRepository();
        if ($rep->checkAuth()) {
            $rep->logData("Products list was viewed. Access Key: " . $rep->getKey());
        }
        return $this->respondWithData($products);
    }
}
