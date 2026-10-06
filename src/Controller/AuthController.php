<?php

namespace App\Controller;

use App\Dto\RegisterRequestDto;
use App\Entity\UserSerializer;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/auth')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly UserService $userService,
        private readonly UserSerializer $serializer,
    ) {
    }

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new RegisterRequestDto($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }

        try {
            $user = $this->userService->createUser($dto);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        }

        return $this->json($this->serializer->toArray($user));
    }

    #[Route('/login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Send email & password to receive a JWT token',
        ]);
    }
}
