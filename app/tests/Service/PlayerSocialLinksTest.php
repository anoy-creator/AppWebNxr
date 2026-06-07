<?php

namespace App\Tests\Service;

use App\Service\PlayerSocialLinks;
use PHPUnit\Framework\TestCase;

class PlayerSocialLinksTest extends TestCase
{
    public function testNormalizeKeepsOnlyAllowedPublicUrls(): void
    {
        $socialLinks = new PlayerSocialLinks();

        self::assertSame([
            'tiktok' => 'https://tiktok.com/@nxr',
            'youtube' => 'https://youtube.com/@nxr',
            'twitter' => 'https://twitter.com/nxr',
        ], $socialLinks->normalize([
            'twitter' => 'https://twitter.com/nxr',
            'twitch' => 'https://twitch.tv/nxr',
            'youtube' => 'https://youtube.com/@nxr',
            'tiktok' => 'https://tiktok.com/@nxr',
            'discord' => '',
        ]));
    }

    public function testNormalizeRejectsHandlesAndDiscordIds(): void
    {
        $this->expectException(\RuntimeException::class);

        (new PlayerSocialLinks())->normalize([
            'discord' => '374571291539144727',
            'twitter' => '@nxr',
        ]);
    }
}
