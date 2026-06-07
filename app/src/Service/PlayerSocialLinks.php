<?php

namespace App\Service;

final class PlayerSocialLinks
{
    public const AllowedNetworks = ['tiktok', 'youtube', 'discord', 'insta', 'twitter'];

    public const Labels = [
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'discord' => 'Discord',
        'insta' => 'Instagram',
        'twitter' => 'Twitter',
    ];

    /**
     * @return string[]
     */
    public function allowedNetworks(): array
    {
        return self::AllowedNetworks;
    }

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return self::Labels;
    }

    /**
     * @return array<string, string>
     */
    public function normalize(mixed $socials, bool $strict = true): array
    {
        $payload = $this->decodePayload($socials);
        $normalized = [];
        $invalidNetworks = [];

        foreach (self::AllowedNetworks as $network) {
            $value = $payload[$network] ?? null;

            if (!is_scalar($value) && !$value instanceof \Stringable) {
                continue;
            }

            $url = trim((string) $value);

            if ('' === $url) {
                continue;
            }

            if (!$this->isPublicHttpUrl($url)) {
                if ($strict) {
                    $invalidNetworks[] = self::Labels[$network];
                }

                continue;
            }

            $normalized[$network] = $url;
        }

        if ($strict && [] !== $invalidNetworks) {
            throw new \RuntimeException(sprintf('Lien invalide pour %s. Utilise une URL complete en http ou https.', implode(', ', $invalidNetworks)));
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(mixed $socials): array
    {
        if (is_array($socials)) {
            return $socials;
        }

        if (!is_string($socials) || '' === trim($socials)) {
            return [];
        }

        $decoded = json_decode($socials, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function isPublicHttpUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }
}
