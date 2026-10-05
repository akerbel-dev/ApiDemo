<?php

namespace App\Controller;

use App\Dto\CreateUserRequestDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/users', name: 'user_')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/email/{email}', name: 'get', methods: ['GET'], requirements: ['email' => '.+@.+'])]
    public function getByEmail(string $email): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new CreateUserRequestDto($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }

        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            return new JsonResponse(['error' => 'User already exists'], 409);
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $dto->password
        );
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
        ], 201);
    }
}
