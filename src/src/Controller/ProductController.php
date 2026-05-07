<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService,
    )
    {
    }

    #[Route('', name: 'product_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $this->productService->getAll(),
        ]);
    }

    #[Route('/create', name: 'product_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->productService->create($product);
                $this->addFlash('success', 'Product created successfully.');

                return $this->redirectToRoute('product_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to create product.');
            }
        }

        return $this->render('product/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found.');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->productService->update($product);
                $this->addFlash('success', 'Product updated successfully.');

                return $this->redirectToRoute('product_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to update product.');
            }
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'product_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            throw $this->createNotFoundException('Product not found.');
        }

        try {
            $this->productService->delete($product);
            $this->addFlash('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to delete product.');
        }

        return $this->redirectToRoute('product_index');
    }
}
