<?php

namespace App\Controller;

use App\Dto\ChangePasswordRequestDto;
use App\Dto\PaginationDto;
use App\Dto\UpdateUserRequestDto;
use App\Entity\User;
use App\Entity\UserSerializer;
use App\Security\UserVoter;
use App\Service\PasswordService;
use App\Service\UserService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/user', name: 'user_')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly UserSerializer $serializer,
        private readonly UserService $userService,
        private readonly PasswordService $passwordService,
    ) {
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[OA\Response(
        response: 200,
        description: 'Returns user details',
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
    #[OA\Response(response: 403, description: 'Forbidden (UserVoter::VIEW denied)')]
    public function get(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::VIEW, $user);

        return $this->json($this->serializer->toArray($user));
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'User updated successfully',
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
    #[OA\Response(response: 409, description: 'Domain conflict error')]
    #[OA\Response(response: 403, description: 'Forbidden (UserVoter::EDIT denied)')]
    public function edit(
        User $user,
        Request $request,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new UpdateUserRequestDto($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }

        try {
            $updated = $this->userService->updateUser($user, $dto);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        }

        return $this->json($this->serializer->toArray($updated));
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[OA\Response(
        response: 200,
        description: 'User soft deleted',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'soft deleted')
            ]
        )
    )]
    #[OA\Response(response: 403, description: 'Forbidden (requires ROLE_ADMIN)')]
    public function delete(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($user->isDeleted()) {
            return new JsonResponse(['status' => 'already deleted']);
        }

        $this->userService->softDelete($user);

        return new JsonResponse(['status' => 'soft deleted']);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    #[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer'), example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer'), example: 20)]
    #[OA\Response(
        response: 200,
        description: 'Paginated list of active users',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'page', type: 'integer'),
                new OA\Property(property: 'limit', type: 'integer'),
                new OA\Property(property: 'total', type: 'integer'),
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'email', type: 'string'),
                            new OA\Property(property: 'firstName', type: 'string'),
                            new OA\Property(property: 'lastName', type: 'string'),
                            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 403, description: 'Forbidden (requires ROLE_ADMIN)')]
    public function list(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $pagination = PaginationDto::fromRequest($request);

        $users = $this->userService->listActiveUsers($pagination);
        $total = $this->userService->countActiveUsers();

        return $this->json([
            'page' => $pagination->page,
            'limit' => $pagination->limit,
            'total' => $total,
            'items' => array_map(
                fn (User $u) => $this->serializer->toArray($u),
                $users
            ),
        ]);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    #[OA\Parameter(name: 'email', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'firstName', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'lastName', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'role', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: 'Search results',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'page', type: 'integer'),
                new OA\Property(property: 'limit', type: 'integer'),
                new OA\Property(property: 'total', type: 'integer'),
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'email', type: 'string'),
                            new OA\Property(property: 'firstName', type: 'string'),
                            new OA\Property(property: 'lastName', type: 'string'),
                            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 403, description: 'Forbidden (requires ROLE_ADMIN)')]
    public function search(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $email = $request->query->get('email');
        $firstName = $request->query->get('firstName');
        $lastName = $request->query->get('lastName');
        $role = $request->query->get('role');

        $pagination = PaginationDto::fromRequest($request);

        $results = $this->userService->searchUsers(
            email: $email,
            firstName: $firstName,
            lastName: $lastName,
            role: $role,
            pagination: $pagination
        );

        $total = $this->userService->countSearchUsers(
            email: $email,
            firstName: $firstName,
            lastName: $lastName,
            role: $role
        );

        return $this->json([
            'page' => $pagination->page,
            'limit' => $pagination->limit,
            'total' => $total,
            'items' => array_map(
                fn (User $u) => $this->serializer->toArray($u),
                $results
            ),
        ]);
    }

    #[Route('/change-password/{id}', name: 'change_password', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['oldPassword', 'newPassword'],
            properties: [
                new OA\Property(property: 'oldPassword', type: 'string'),
                new OA\Property(
                    property: 'newPassword',
                    type: 'string',
                    description: 'Must be at least 8 chars, contain uppercase, lowercase, number, and special character.',
                    minLength: 8,
                    pattern: '^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W]).+$',
                    example: 'StrongPass123!'
                    ),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Password changed')]
    #[OA\Response(response: 400, description: 'Invalid password or validation error')]
    #[OA\Response(response: 403, description: 'Forbidden (UserVoter::CHANGE_PASSWORD denied)')]
    public function changePassword(User $user, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::CHANGE_PASSWORD, $user);

        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new ChangePasswordRequestDto($data);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }

        $current = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $current->getRoles(), true);

        try {
            $this->passwordService->changePassword($user, $dto, $isAdmin);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }

        return $this->json(['status' => 'password changed']);
    }
}
