<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserDiscordBasics(): void
    {
        $createdAt = new \DateTimeImmutable('2026-01-01 10:00:00');
        $lastLoginAt = new \DateTimeImmutable('2026-01-02 11:00:00');

        $user = new User();

        $user
            ->setDiscordId('123456789')
            ->setUsername('ShadowX')
            ->setDiscriminator('0001')
            ->setAvatar('avatar_hash')
            ->setEmail('shadowx@example.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setCreatedAt($createdAt)
            ->setLastLoginAt($lastLoginAt);

        $this->assertNull($user->getId());
        $this->assertSame('123456789', $user->getDiscordId());
        $this->assertSame('ShadowX', $user->getUsername());
        $this->assertSame('0001', $user->getDiscriminator());
        $this->assertSame('avatar_hash', $user->getAvatar());
        $this->assertSame('shadowx@example.com', $user->getEmail());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertSame('123456789', $user->getUserIdentifier());
        $this->assertSame($createdAt, $user->getCreatedAt());
        $this->assertSame($lastLoginAt, $user->getLastLoginAt());
    }

    public function testDefaultRoleIsUser(): void
    {
        $user = new User();

        $this->assertContains('ROLE_USER', $user->getRoles());
    }
}
