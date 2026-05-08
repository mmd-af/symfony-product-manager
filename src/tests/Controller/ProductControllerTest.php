<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProductControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private int $categoryId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $conn = $em->getConnection();

        // Clean all tables respecting FK order
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('TRUNCATE TABLE product');
        $conn->executeStatement('TRUNCATE TABLE category');
        $conn->executeStatement('TRUNCATE TABLE user');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        // Create test category
        $category = new Category();
        $category->setName('Test Category');
        $em->persist($category);
        $em->flush();
        $this->categoryId = $category->getId();

        // Create and login test user
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get('security.user_password_hasher');
        $user = (new User())->setEmail('test@example.com');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $em->persist($user);
        $em->flush();

        $this->client->loginUser($user);
    }

    public function testIndexIsSuccessful(): void
    {
        $this->client->request('GET', '/products');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('table');
    }

    public function testCreatePageIsSuccessful(): void
    {
        $this->client->request('GET', '/products/create');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
    }

    public function testCreateProductWithValidData(): void
    {
        $this->client->request('GET', '/products/create');

        $this->client->submitForm('Save', [
            'product[name]' => 'Test Product',
            'product[price]' => '29.99',
            'product[description]' => 'A test description',
            'product[category]' => $this->categoryId,
        ]);

        self::assertResponseRedirects('/products');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Product created successfully.');
    }

    public function testCreateProductWithInvalidData(): void
    {
        $this->client->request('GET', '/products/create');

        $this->client->submitForm('Save', [
            'product[name]' => '',
            'product[price]' => '-1',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('form');
        self::assertSelectorExists('[aria-invalid="true"]');
    }

    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/products');

        self::assertResponseRedirects('http://localhost/login');
    }
}
