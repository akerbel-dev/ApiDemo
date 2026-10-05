<?php

namespace App\Controller;

use App\Dto\UpdateUserRequestDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/user', name: 'user_')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $current = $this->getUser();
        if (!$current) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        $isAdmin = in_array('ROLE_ADMIN', $current->getRoles(), true);
        if (!$isAdmin && $current->getId() !== $id) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $user = $this->userRepository->find($id);

        if (!$user || ($user->isDeleted() && !$isAdmin)) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'deletedAt' => $user->getDeletedAt()?->format('c'),
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Pagination parameters
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        // Hard cap at 100 to avoid huge responses

        $offset = ($page - 1) * $limit;

        $users = $this->userRepository->findAllActive($limit, $offset);
        $total = $this->userRepository->countActive();

        $data = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'deletedAt' => $user->getDeletedAt()?->format('c'),
            ];
        }, $users);

        return new JsonResponse([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'items' => $data,
        ]);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): JsonResponse
    {
        $current = $this->getUser();
        if (!$current) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }
        $this->denyAccessUnlessGranted('ROLE_USER');

        $isAdmin = in_array('ROLE_ADMIN', $current->getRoles(), true);
        if (!$isAdmin && $current->getId() !== $id) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new UpdateUserRequestDto($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }

        if (null !== $dto->email) {
            $existing = $this->userRepository->findOneBy(['email' => $dto->email]);
            if ($existing && $existing->getId() !== $id) {
                return new JsonResponse(['error' => 'Email already taken'], 409);
            }
            $user->setEmail($dto->email);
        }

        if (array_key_exists('firstName', $data)) {
            $user->setFirstName($dto->firstName);
        }

        if (array_key_exists('lastName', $data)) {
            $user->setLastName($dto->lastName);
        }

        if (array_key_exists('password', $data) && null !== $dto->password) {
            $hashed = $this->passwordHasher->hashPassword($user, $dto->password);
            $user->setPassword($hashed);
        }

        $this->em->flush();

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        if ($user->isDeleted()) {
            return new JsonResponse(['status' => 'already deleted']);
        }

        $user->softDelete();
        $this->em->flush();

        return new JsonResponse(['status' => 'soft deleted']);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $email = $request->query->get('email');
        $firstName = $request->query->get('firstName');
        $lastName = $request->query->get('lastName');
        $role = $request->query->get('role');

        // Pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $results = $this->userRepository->searchUsers($email, $firstName, $lastName, $role, $limit, $offset);
        $total = $this->userRepository->countSearchUsers($email, $firstName, $lastName, $role);

        $items = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'deletedAt' => $user->getDeletedAt()?->format('c'),
            ];
        }, $results);

        return new JsonResponse([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'items' => $items,
        ]);
    }
}
