<?php

declare(strict_types=1);

namespace App\Tests;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $conn = $em->getConnection();

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $conn->executeStatement('TRUNCATE TABLE user');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        $this->userRepository = $container->get(UserRepository::class);
    }

    public function testRegistrationPageLoads(): void
    {
        $this->client->request('GET', '/register');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Register');
    }

    public function testRegisterNewUser(): void
    {
        $this->client->request('GET', '/register');

        $this->client->submitForm('Register', [
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[plainPassword]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);

        self::assertCount(1, $this->userRepository->findAll());
    }

    public function testRegisterWithDuplicateEmail(): void
    {
        // Register first time
        $this->client->request('GET', '/register');
        $this->client->submitForm('Register', [
            'registration_form[email]' => 'duplicate@example.com',
            'registration_form[plainPassword]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);

        // Try registering again with the same email
        $this->client->request('GET', '/register');
        $this->client->submitForm('Register', [
            'registration_form[email]' => 'duplicate@example.com',
            'registration_form[plainPassword]' => 'password123',
            'registration_form[agreeTerms]' => true,
        ]);

        // Should stay on register page — only 1 user in DB
        self::assertCount(1, $this->userRepository->findAll());
    }
}
