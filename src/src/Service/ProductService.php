<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\ProductRepositoryInterface;
use App\Entity\Product;

final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    )
    {
    }

    /**
     * @return Product[]
     */
    public function getAll(): array
    {
        return $this->productRepository->findAll();
    }

    public function findById(int $id): ?Product
    {
        return $this->productRepository->findById($id);
    }

    public function create(Product $product): void
    {
        $this->productRepository->save($product);
    }

    public function update(Product $product): void
    {
        $this->productRepository->save($product);
    }

    public function delete(Product $product): void
    {
        $this->productRepository->delete($product);
    }
}
