<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserVoter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

#[CoversClass(UserVoter::class)]
final class UserVoterTest extends TestCase
{
    private function createToken(mixed $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    #[Test]
    public function unsupportedAttributeResultsInAbstain(): void
    {
        $voter = new UserVoter();

        $subject = new User();
        $token = $this->createToken($subject);

        $result = $voter->vote($token, $subject, ['UNSUPPORTED']);

        $this->assertSame(Voter::ACCESS_ABSTAIN, $result);
    }

    #[Test]
    public function unsupportedSubjectResultsInAbstain(): void
    {
        $voter = new UserVoter();

        $token = $this->createToken(new User());

        $result = $voter->vote($token, new \stdClass(), [UserVoter::VIEW]);

        $this->assertSame(Voter::ACCESS_ABSTAIN, $result);
    }

    #[Test]
    public function adminUserIsAlwaysGranted(): void
    {
        $voter = new UserVoter();

        $admin = (new User())->setRoles(['ROLE_ADMIN']);
        $subject = new User();

        $token = $this->createToken($admin);

        $result = $voter->vote($token, $subject, [UserVoter::EDIT]);

        $this->assertSame(Voter::ACCESS_GRANTED, $result);
    }

    #[Test]
    public function sameUserIsGranted(): void
    {
        $voter = new UserVoter();

        $user = new User();
        $subject = $user; // same user

        $token = $this->createToken($user);

        $result = $voter->vote($token, $subject, [UserVoter::DELETE]);

        $this->assertSame(Voter::ACCESS_GRANTED, $result);
    }
}
