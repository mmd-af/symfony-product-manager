<?php

namespace App\Contract;

use App\Entity\Product;

interface ProductRepositoryInterface
{
    /**
     * Returns all products from the database.
     *
     * @return Product[]
     */
    public function findAll(): array;

    /**
     * Finds a single product by its ID.
     * Returns null if no product is found.
     *
     * @param int $id The product ID
     * @return Product|null
     */
    public function findById(int $id): ?Product;

    /**
     * Persists a product to the database.
     * Use this for both creating and updating a product.
     *
     * @param Product $product
     * @param bool $flush Whether to flush the entity manager immediately
     */
    public function save(Product $product, bool $flush = true): void;

    /**
     * Removes a product from the database permanently.
     *
     * @param Product $product
     * @param bool $flush Whether to flush the entity manager immediately
     */
    public function delete(Product $product, bool $flush = true): void;
}
