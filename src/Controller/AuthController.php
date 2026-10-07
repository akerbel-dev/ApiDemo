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
use OpenApi\Attributes as OA;

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
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'firstName', 'lastName', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
                new OA\Property(
                    property: 'password',
                    type: 'string',
                    description: 'Must be at least 8 characters long and contain uppercase, lowercase, number, and special character.',
                    minLength: 8,
                    pattern: '^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W]).+$',
                    example: 'StrongPass123!'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'User successfully created',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Validation error')]
    #[OA\Response(response: 409, description: 'Conflict or domain error')]
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
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    example: 'user@example.com'
                    ),
                new OA\Property(
                    property: 'password',
                    type: 'string',
                    description: 'User password',
                    example: 'StrongPass123!'
                    ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'JWT token returned on successful authentication',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'token',
                    type: 'string',
                    description: 'JWT access token',
                    example: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'
                    )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid credentials',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Invalid credentials.'
                    )
            ]
        )
    )]
    public function login(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Send email & password to receive a JWT token',
        ]);
    }
}
