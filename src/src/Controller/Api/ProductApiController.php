<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Category;
use App\Entity\Product;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products')]
final class ProductApiController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    #[Route('', name: 'api_product_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $products = $this->productService->getAll();

        return $this->json(
            array_map(fn(Product $p) => $this->toArray($p), $products)
        );
    }

    #[Route('/{id}', name: 'api_product_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            return $this->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->toArray($product));
    }

    #[Route('', name: 'api_product_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        $category = $this->findCategory($data['category_id'] ?? null);
        if (!$category) {
            return $this->json(['errors' => ['category_id' => 'Category not found.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $product = new Product();
        $product->setName($data['name'] ?? null);
        $product->setPrice($data['price'] ?? null);
        $product->setDescription($data['description'] ?? null);
        $product->setCategory($category);

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return $this->json($this->formatErrors($errors), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->productService->create($product);
            return $this->json($this->toArray($product), Response::HTTP_CREATED);
        } catch (\Exception) {
            return $this->json(['error' => 'Failed to create product.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_product_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            return $this->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('name', $data)) {
            $product->setName($data['name']);
        }
        if (array_key_exists('price', $data)) {
            $product->setPrice($data['price']);
        }
        if (array_key_exists('description', $data)) {
            $product->setDescription($data['description']);
        }
        if (array_key_exists('category_id', $data)) {
            $category = $this->findCategory($data['category_id']);
            if (!$category) {
                return $this->json(['errors' => ['category_id' => 'Category not found.']], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $product->setCategory($category);
        }

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return $this->json($this->formatErrors($errors), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->productService->update($product);
            return $this->json($this->toArray($product));
        } catch (\Exception) {
            return $this->json(['error' => 'Failed to update product.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_product_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            return $this->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->productService->delete($product);
            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception) {
            return $this->json(['error' => 'Failed to delete product.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function toArray(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'description' => $product->getDescription(),
            'category' => [
                'id' => $product->getCategory()?->getId(),
                'name' => $product->getCategory()?->getName(),
            ],
            'created_at' => $product->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $product->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    private function findCategory(mixed $id): ?Category
    {
        if ($id === null || (!is_int($id) && !is_string($id))) {
            return null;
        }

        return $this->entityManager->getRepository(Category::class)->find((int)$id);
    }

    private function formatErrors(ConstraintViolationListInterface $errors): array
    {
        $result = [];
        foreach ($errors as $error) {
            $result[$error->getPropertyPath()] = $error->getMessage();
        }

        return ['errors' => $result];
    }
}
