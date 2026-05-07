<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products')]
final class ProductController extends AbstractController
{
    public function __construct(
        private ProductService $productService,
    )
    {
    }

    #[Route('', name: 'product_index', methods: ['GET'])]
    public function index(): Response
    {
        $products = $this->productService->getAll();

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }
}
