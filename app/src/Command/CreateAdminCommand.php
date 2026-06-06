<?php

namespace App\Command;

use App\Entity\Player;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:create-admin')
            ->setDescription('Crée un utilisateur administrateur')
            ->addArgument('username', InputArgument::OPTIONAL, 'Username')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        if (!$helper instanceof QuestionHelper) {
            throw new \LogicException('Question helper is not available.');
        }

        $username = $this->readNonEmptyString($input->getArgument('username'));
        if (null === $username) {
            $question = new Question('Nom d\'utilisateur: ');
            $username = $this->readNonEmptyString($helper->ask($input, $output, $question));
        }

        if (null === $username) {
            $output->writeln('<error>Le nom d\'utilisateur est obligatoire.</error>');

            return Command::FAILURE;
        }

        $password = $this->readNonEmptyString($input->getArgument('password'), false);
        if (null === $password) {
            $question = new Question('Mot de passe: ');
            $question->setHidden(true);
            $password = $this->readNonEmptyString($helper->ask($input, $output, $question), false);
        }

        if (null === $password) {
            $output->writeln('<error>Le mot de passe est obligatoire.</error>');

            return Command::FAILURE;
        }

        $discordId = 'admin-'.uniqid();

        $user = new User();
        $user->setUsername($username);
        $user->setDiscordId($discordId);
        $user->setDiscordName($username);
        $user->setRoles(['ROLE_ADMIN']);

        $player = new Player();
        $player
            ->setPseudo($username)
            ->setDiscordId($discordId)
            ->setAvatar('')
            ->setRole('Staff')
            ->setGrade('Admin')
            ->setGame('All Games')
            ->setSocials(['discord' => $discordId]);

        $user->setPlayer($player);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($player);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>✅ Admin créé avec succès!</info>');
        $output->writeln("Username: <fg=cyan>$username</>");

        return Command::SUCCESS;
    }

    private function readNonEmptyString(mixed $value, bool $trim = true): ?string
    {
        if (!is_scalar($value) && !$value instanceof \Stringable) {
            return null;
        }

        $value = (string) $value;

        if ($trim) {
            $value = trim($value);
        }

        return '' === $value ? null : $value;
    }
}
