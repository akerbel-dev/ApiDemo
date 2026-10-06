<?php

namespace App\Controller;

use App\Dto\ChangePasswordRequestDto;
use App\Dto\UpdateUserRequestDto;
use App\Entity\User;
use App\Entity\UserSerializer;
use App\Repository\UserRepository;
use App\Security\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/user', name: 'user_')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserSerializer $userSerializer,
    ) {
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->denyAccessUnlessGranted(UserVoter::VIEW, $user);

        return $this->json($this->userSerializer->toArray($user));
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

        $data = array_map(fn (User $user) => $this->userSerializer->toArray($user), $users);

        return new JsonResponse([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'items' => $data,
        ]);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function edit(User $user, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

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

        return $this->json($this->userSerializer->toArray($user));
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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

        $items = array_map(fn (User $user) => $this->userSerializer->toArray($user), $results);

        return new JsonResponse([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'items' => $items,
        ]);
    }

    #[Route('/change-password/{id}', name: 'change_password', methods: ['POST'])]
    public function changePassword(User $user, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new ChangePasswordRequestDto($data);
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }

        $oldPassword = $dto->oldPassword;
        $newPassword = $dto->newPassword;

        if (!$newPassword) {
            return new JsonResponse(['error' => 'New password is required'], 400);
        }

        if (!$isAdmin) {
            if (!$oldPassword) {
                return new JsonResponse(['error' => 'Old password is required'], 400);
            }

            if (!$this->passwordHasher->isPasswordValid($user, $oldPassword)) {
                return new JsonResponse(['error' => 'Old password is incorrect'], 403);
            }
        }

        $hashed = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashed);

        $this->em->flush();

        return new JsonResponse(['status' => 'password changed']);
    }
}
