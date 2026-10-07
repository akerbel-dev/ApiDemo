<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user:promote',
    description: 'Promotes an existing user to admin role'
)]
final class PromoteUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
        ->addArgument('email', InputArgument::REQUIRED, 'Email of the user to promote');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $output->writeln("<error>User with email '$email' not found.</error>");

            return Command::FAILURE;
        }

        $user->setRoles(['ROLE_ADMIN']);
        $this->em->flush();

        $output->writeln("<info>User '$email' has been promoted to admin.</info>");

        return Command::SUCCESS;
    }
}
