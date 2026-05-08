<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Contract\ProductRepositoryInterface;
use App\Entity\Product;
use App\Service\ProductService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProductServiceTest extends TestCase
{
    /** @var MockObject&ProductRepositoryInterface */
    private MockObject $repository;
    private ProductService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductRepositoryInterface::class);
        $this->service = new ProductService($this->repository);
    }

    public function testGetAllReturnsProducts(): void
    {
        $product = new Product();

        $this->repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$product]);

        $result = $this->service->getAll();

        self::assertCount(1, $result);
        self::assertSame($product, $result[0]);
    }

    public function testGetAllReturnsEmptyArray(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([]);

        self::assertSame([], $this->service->getAll());
    }

    public function testFindByIdReturnsProduct(): void
    {
        $product = new Product();

        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(1)
            ->willReturn($product);

        self::assertSame($product, $this->service->findById(1));
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        self::assertNull($this->service->findById(999));
    }

    public function testCreateCallsSaveOnce(): void
    {
        $product = new Product();

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with($product);

        $this->service->create($product);
    }

    public function testUpdateCallsSaveOnce(): void
    {
        $product = new Product();

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with($product);

        $this->service->update($product);
    }

    public function testDeleteCallsDeleteOnce(): void
    {
        $product = new Product();

        $this->repository
            ->expects(self::once())
            ->method('delete')
            ->with($product);

        $this->service->delete($product);
    }

    public function testCreateNeverCallsDelete(): void
    {
        $product = new Product();

        $this->repository->expects(self::never())->method('delete');
        $this->repository->expects(self::once())->method('save');

        $this->service->create($product);
    }

    public function testDeleteNeverCallsSave(): void
    {
        $product = new Product();

        $this->repository->expects(self::never())->method('save');
        $this->repository->expects(self::once())->method('delete');

        $this->service->delete($product);
    }
}
