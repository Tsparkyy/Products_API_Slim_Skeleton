<?php
declare(strict_types=1);

namespace App\Application\Actions\Product;

use Psr\Http\Message\ResponseInterface as Response;
use App\Infrastructure\Persistence\Product\InMemoryProductRepository;

class ViewProductAction extends ProductAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $productId = (string) $this->resolveArg('id');
        $product = $this->productRepository->findProductOfId((string) $this->args['id']);
        $rep = new InMemoryProductRepository();
        if ($rep->checkAuth()) {
            $rep->logData("Product of id `${productId}` was viewed. Access Key: " . $rep->getKey());
            $this->logger->info("Product of id `${productId}` was viewed.");
        }
        return $this->respondWithData($product);
    }
}
