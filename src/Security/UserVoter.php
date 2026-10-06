<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class UserVoter extends Voter
{
    public const VIEW = 'USER_VIEW';
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';
    public const CHANGE_PASSWORD = 'USER_CHANGE_PASSWORD';

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof User && in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::CHANGE_PASSWORD,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $current = $token->getUser();
        if (!$current instanceof User) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $current->getRoles(), true)) {
            return true;
        }

        return $current->getId() === $subject->getId();
    }
}
