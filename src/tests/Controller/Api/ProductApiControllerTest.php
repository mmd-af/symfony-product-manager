<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductApiControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private int $categoryId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $conn = $em->getConnection();

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('TRUNCATE TABLE product');
        $conn->executeStatement('TRUNCATE TABLE category');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        $category = new Category();
        $category->setName('Test Category');
        $em->persist($category);
        $em->flush();

        $this->categoryId = $category->getId();
    }

    public function testGetAllReturnsJsonArray(): void
    {
        $this->client->request('GET', '/api/products');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
    }

    public function testGetAllReturnsEmptyArrayWhenNoProducts(): void
    {
        $this->client->request('GET', '/api/products');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(0, $data);
    }

    public function testGetSingleProductNotFound(): void
    {
        $this->client->request('GET', '/api/products/99999');

        self::assertResponseStatusCodeSame(404);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testCreateProductWithValidData(): void
    {
        $this->client->request('POST', '/api/products', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'API Product',
            'price' => '49.99',
            'description' => 'Created via API',
            'category_id' => $this->categoryId,
        ]));

        self::assertResponseStatusCodeSame(201);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('API Product', $data['name']);
        self::assertSame('49.99', $data['price']);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('created_at', $data);
    }

    public function testCreateProductWithInvalidData(): void
    {
        $this->client->request('POST', '/api/products', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => '',
            'price' => '-1',
            'category_id' => $this->categoryId,
        ]));

        self::assertResponseStatusCodeSame(422);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $data);
        self::assertArrayHasKey('name', $data['errors']);
        self::assertArrayHasKey('price', $data['errors']);
    }

    public function testCreateProductWithInvalidJson(): void
    {
        $this->client->request('POST', '/api/products', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'not-valid-json');

        self::assertResponseStatusCodeSame(400);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testCreateProductWithMissingCategory(): void
    {
        $this->client->request('POST', '/api/products', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Product',
            'price' => '10.00',
            'category_id' => 99999,
        ]));

        self::assertResponseStatusCodeSame(422);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('errors', $data);
    }

    public function testUpdateProduct(): void
    {
        // First create a product
        $this->client->request('POST', '/api/products', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Original',
            'price' => '10.00',
            'category_id' => $this->categoryId,
        ]));

        $created = json_decode($this->client->getResponse()->getContent(), true);

        // Then update it
        $this->client->request('PUT', '/api/products/' . $created['id'], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Updated', 'price' => '99.99']));

        self::assertResponseIsSuccessful();

        $updated = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('Updated', $updated['name']);
        self::assertSame('99.99', $updated['price']);
    }

    public function testUpdateNonExistentProduct(): void
    {
        $this->client->request('PUT', '/api/products/99999', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Updated']));

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteProduct(): void
    {
        // Create first
        $this->client->request('POST', '/api/products', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'To Delete',
            'price' => '5.00',
            'category_id' => $this->categoryId,
        ]));

        $created = json_decode($this->client->getResponse()->getContent(), true);

        // Then delete
        $this->client->request('DELETE', '/api/products/' . $created['id']);
        self::assertResponseStatusCodeSame(204);

        // Verify it's gone
        $this->client->request('GET', '/api/products/' . $created['id']);
        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteNonExistentProduct(): void
    {
        $this->client->request('DELETE', '/api/products/99999');

        self::assertResponseStatusCodeSame(404);
    }
}
