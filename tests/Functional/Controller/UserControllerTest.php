<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class UserControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private string $adminPassword = 'Admin12!';

    private string $userPassword = 'User123!';

    private function loginAs(User $user, string $password, KernelBrowser $client): void
    {
        $client->jsonRequest('POST', '/auth/login', [
            'email' => $user->getEmail(),
            'roles' => ['ROLE_USER'],
            'password' => $password,
        ]);

        $data = json_decode($client->getResponse()->getContent(), true);
        if (!isset($data['token'])) {
            throw new \RuntimeException('Login failed: '.json_encode($data));
        }
        $client->setServerParameter('HTTP_Authorization', 'Bearer '.$data['token']);
    }

    private function loginAsAdmin(KernelBrowser $client): User
    {
        $admin = UserFactory::createOne([
            'email' => 'admin@example.com',
            'roles' => ['ROLE_ADMIN'],
            'password' => $this->adminPassword,
        ]);

        $this->loginAs($admin, $this->adminPassword, $client);

        return $admin;
    }

    private function loginAsUser(KernelBrowser $client): User
    {
        $user = UserFactory::createOne([
            'email' => 'user@example.com',
            'password' => $this->userPassword,
        ]);

        $this->loginAs($user, $this->userPassword, $client);

        return $user;
    }

    #[Test]
    public function getUserSuccess(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'email' => 'target@example.com',
            'roles' => ['ROLE_USER'],
            'password' => 'Target123!',
        ]);

        $admin = $this->loginAsAdmin($client);

        $client->jsonRequest('GET', '/user/'.$user->getId());
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('target@example.com', $data['email']);
    }

    #[Test]
    public function getUserDeniedForNonOwner(): void
    {
        $client = static::createClient();

        $admin = UserFactory::createOne([
            'email' => 'admin2@example.com',
            'roles' => ['ROLE_ADMIN'],
            'password' => $this->adminPassword,
        ]);

        $user = $this->loginAsUser($client);

        $client->jsonRequest('GET', '/user/'.$admin->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function editUserSuccess(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'email' => 'editme@example.com',
            'roles' => ['ROLE_USER'],
            'password' => 'Edit123!',
        ]);

        $admin = $this->loginAsAdmin($client);

        $client->jsonRequest('PUT', '/user/'.$user->getId(), [
            'firstName' => 'UpdatedFirstName',
            'lastName' => 'UpdatedLastName',
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('UpdatedFirstName', $data['firstName']);
        $this->assertSame('UpdatedLastName', $data['lastName']);
    }

    #[Test]
    public function deleteUserSuccess(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'email' => 'delete@example.com',
            'roles' => ['ROLE_USER'],
            'password' => 'Delete123!',
        ]);

        $admin = $this->loginAsAdmin($client);

        $client->request('DELETE', '/user/'.$user->getId());
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('soft deleted', $data['status']);
    }

    #[Test]
    public function deleteUserAlreadyDeleted(): void
    {
        $client = static::createClient();

        $user = UserFactory::createOne([
            'email' => 'already@example.com',
            'roles' => ['ROLE_USER'],
            'password' => 'Already123!',
            'deletedAt' => new \DateTimeImmutable(),
        ]);

        $admin = $this->loginAsAdmin($client);

        $client->request('DELETE', '/user/'.$user->getId());
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('already deleted', $data['status']);
    }

    #[Test]
    public function listUsers(): void
    {
        $client = static::createClient();

        UserFactory::createOne([
            'email' => 'u1@example.com',
            'roles' => ['ROLE_USER'],
            'password' => 'U1pass!',
        ]);

        UserFactory::createOne([
            'email' => 'u2@example.com',
            'roles' => ['ROLE_USER'],
            'password' => 'U2pass!',
        ]);

        $admin = $this->loginAsAdmin($client);

        $client->request('GET', '/user/list?page=1&limit=10');
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(1, $data['page']);
        $this->assertSame(10, $data['limit']);
        $this->assertGreaterThanOrEqual(2, $data['total']);
        $this->assertCount(3, $data['items']);
    }

    #[Test]
    public function searchUsers(): void
    {
        $client = static::createClient();

        UserFactory::createOne([
            'email' => 'searchme@example.com',
            'roles' => ['ROLE_USER'],
            'password' => 'Search123!',
        ]);

        $admin = $this->loginAsAdmin($client);

        $client->request('GET', '/user/search?email=searchme@example.com');
        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(1, $data['total']);
        $this->assertSame('searchme@example.com', $data['items'][0]['email']);
    }

    #[Test]
    public function changePasswordSuccess(): void
    {
        $client = static::createClient();

        $user = $this->loginAsUser($client);

        $client->jsonRequest('POST', '/user/change-password/'.$user->getId(), [
            'oldPassword' => $this->userPassword,
            'newPassword' => 'NewPass123!',
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('password changed', $data['status']);
    }

    #[Test]
    public function changePasswordInvalid(): void
    {
        $client = static::createClient();

        $user = $this->loginAsUser($client);

        $client->jsonRequest('POST', '/user/change-password/'.$user->getId(), [
            'oldPassword' => 'WrongPassword',
            'newPassword' => 'NewPass123!',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function changePasswordForbidden(): void
    {
        $client = static::createClient();

        $admin = UserFactory::createOne([
            'email' => 'admin3@example.com',
            'roles' => ['ROLE_ADMIN'],
            'password' => $this->adminPassword,
        ]);

        $this->loginAsUser($client);

        $client->jsonRequest('POST', '/user/change-password/'.$admin->getId(), [
            'oldPassword' => $this->userPassword,
            'newPassword' => 'NewPass123!',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
