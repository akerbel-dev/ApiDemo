<?php

namespace App\Tests\Functional\Controller;

use App\Controller\AuthController;
use App\Entity\User;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(AuthController::class)]
final class AuthControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function registerSuccess(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/auth/register', [
            'email' => 'anton@example.com',
            'firstName' => 'Anton',
            'lastName' => 'Ivanov',
            'password' => 'Strong123!',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('anton@example.com', $data['email']);
        $this->assertSame('Anton', $data['firstName']);
        $this->assertSame('Ivanov', $data['lastName']);

        // Ensure user is actually persisted
        $this->assertNotNull(UserFactory::repository()->findOneBy(['email' => 'anton@example.com']));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function registerValidationError(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/auth/register', [
            'email' => 'not-an-email',
            'firstName' => '',
            'lastName' => '',
            'password' => 'weak',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function registerDuplicateEmail(): void
    {
        $client = static::createClient();

        // Create existing user
        UserFactory::createOne(['email' => 'anton@example.com']);

        $client->jsonRequest('POST', '/auth/register', [
            'email' => 'anton@example.com',
            'firstName' => 'Anton',
            'lastName' => 'Ivanov',
            'password' => 'Strong123!',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('User already exists', $data['error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function loginReturnsMessage(): void
    {
        $client = static::createClient();

        UserFactory::createOne([
            'email' => 'anton@example.com',
            'password' => 'Strong123!',
        ]);

        $client->jsonRequest('POST', '/auth/login', [
            'email' => 'anton@example.com',
            'password' => 'Strong123!',
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $data);
    }
}
