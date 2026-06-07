<?php

namespace App\DataFixtures;

use App\Entity\Roster;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProdFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    /**
     * @return string[]
     */
    public static function getGroups(): array
    {
        return ['prod'];
    }

    public function load(ObjectManager $manager): void
    {
        $adminUsername = $_ENV['NXR_ADMIN_USERNAME'] ?? 'admin';
        $adminPassword = $_ENV['NXR_ADMIN_PASSWORD'] ?? 'adminNxr';

        $admin = new User();
        $admin
            ->setUsername($adminUsername)
            ->setEmail($_ENV['NXR_ADMIN_EMAIL'] ?? 'admin@nxr.local')
            ->setDiscordId($_ENV['NXR_ADMIN_DISCORD_ID'] ?? 'admin-prod')
            ->setDiscordName($_ENV['NXR_ADMIN_DISCORD_NAME'] ?? 'Admin NxR')
            ->setRoles(['ROLE_ADMIN']);

        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, $adminPassword)
        );

        $manager->persist($admin);

        $roster = new Roster();
        $roster
            ->setName('NxR')
            ->setGame('Call of Duty')
            ->setBanner('/build/images/banNaxeria.png')
            ->setWins(0)
            ->setLosses(0)
            ->setWinrate('0%');

        $manager->persist($roster);

        foreach (['NxR Esport', 'Adversaire'] as $teamName) {
            $team = new Team();
            $team->setName($teamName);

            $manager->persist($team);
        }

        $manager->flush();
    }
}
