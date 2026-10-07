<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-demo-users',
    description: 'Creates a demo admin and a demo user for testing the API (dev only)'
)]
class CreateDemoUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly KernelInterface $kernel
        ) {
            parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->kernel->getEnvironment() !== 'dev') {
            $output->writeln('<error>This command can only be executed in the dev environment.</error>');
            return Command::FAILURE;
        }
        
        $repo = $this->em->getRepository(User::class);
        
        $adminEmail = 'admin@example.com';
        $admin = $repo->findOneBy(['email' => $adminEmail]);
        
        if (!$admin) {
            $admin = new User();
            $admin->setEmail($adminEmail);
            $admin->setFirstName('Admin');
            $admin->setLastName('User');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setPassword(
                $this->passwordHasher->hashPassword($admin, 'Admin123!')
                );
            
            $this->em->persist($admin);
            $output->writeln("<info>Created admin: $adminEmail / Admin123!</info>");
        } else {
            $output->writeln("<comment>Admin already exists: $adminEmail</comment>");
        }
        
        $userEmail = 'user@example.com';
        $user = $repo->findOneBy(['email' => $userEmail]);
        
        if (!$user) {
            $user = new User();
            $user->setEmail($userEmail);
            $user->setFirstName('Simple');
            $user->setLastName('User');
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, 'User123!')
                );
            
            $this->em->persist($user);
            $output->writeln("<info>Created user: $userEmail / User123!</info>");
        } else {
            $output->writeln("<comment>User already exists: $userEmail</comment>");
        }
        
        $this->em->flush();
        
        $output->writeln("<info>Demo users ready!</info>");
        
        return Command::SUCCESS;
    }
}
