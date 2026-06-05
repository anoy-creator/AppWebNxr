<?php

namespace App\Service;

class DiscordGuildRoleResolver
{
    private const DiscordApiBaseUrl = 'https://discord.com/api/v10';

    public function memberHasRoleFromUserAccessToken(string $accessToken, ?string $roleId = null): bool
    {
        $guildId = $this->readEnv('DISCORD_GUILD_ID');
        $roleId = trim((string) ($roleId ?? $this->readEnv('DISCORD_ADMIN_ROLE_ID')));
        $accessToken = trim($accessToken);

        if ('' === $accessToken || '' === $guildId || '' === $roleId) {
            return false;
        }

        $response = $this->requestJson(
            sprintf('%s/users/@me/guilds/%s/member', self::DiscordApiBaseUrl, rawurlencode($guildId)),
            'Bearer '.$accessToken,
        );

        return $this->responseHasRole($response, $roleId);
    }

    public function memberHasRole(string $discordId, ?string $roleId = null): bool
    {
        $discordId = trim($discordId);
        $guildId = $this->readEnv('DISCORD_GUILD_ID');
        $roleId = trim((string) ($roleId ?? $this->readEnv('DISCORD_ADMIN_ROLE_ID')));
        $botToken = $this->normalizeBotToken((string) (
            $this->readEnv('DISCORD_BOT_TOKEN')
            ?? $this->readEnv('DISCORD_TOKEN')
            ?? $this->readEnv('TOKEN')
        ));

        if ('' === $discordId || '' === $guildId || '' === $roleId || '' === $botToken) {
            return false;
        }

        $url = sprintf(
            '%s/guilds/%s/members/%s',
            self::DiscordApiBaseUrl,
            rawurlencode($guildId),
            rawurlencode($discordId),
        );

        $response = $this->requestJson($url, 'Bot '.$botToken);

        return $this->responseHasRole($response, $roleId);
    }

    /**
     * @return list<string>
     */
    public function resolveMemberRoleNames(string $discordId): array
    {
        $discordId = trim($discordId);
        $guildId = $this->readEnv('DISCORD_GUILD_ID');
        $botToken = $this->normalizeBotToken((string) (
            $this->readEnv('DISCORD_BOT_TOKEN')
            ?? $this->readEnv('DISCORD_TOKEN')
            ?? $this->readEnv('TOKEN')
        ));

        if ('' === $discordId || '' === $guildId || '' === $botToken) {
            return [];
        }

        $member = $this->requestJson(
            sprintf(
                '%s/guilds/%s/members/%s',
                self::DiscordApiBaseUrl,
                rawurlencode($guildId),
                rawurlencode($discordId),
            ),
            'Bot '.$botToken,
        );

        if (!$member || !isset($member['roles']) || !is_array($member['roles'])) {
            return [];
        }

        $memberRoleIds = array_values(array_unique(array_map('strval', $member['roles'])));

        if ([] === $memberRoleIds) {
            return [];
        }

        $guildRoles = $this->requestJson(
            sprintf('%s/guilds/%s/roles', self::DiscordApiBaseUrl, rawurlencode($guildId)),
            'Bot '.$botToken,
        );

        if (!is_array($guildRoles)) {
            return [];
        }

        $rolesById = [];
        foreach ($guildRoles as $role) {
            if (!is_array($role) || !isset($role['id'], $role['name'])) {
                continue;
            }

            $rolesById[(string) $role['id']] = [
                'name' => (string) $role['name'],
                'position' => (int) ($role['position'] ?? 0),
            ];
        }

        $memberRoles = [];
        foreach ($memberRoleIds as $roleId) {
            if (!isset($rolesById[$roleId])) {
                continue;
            }

            $memberRoles[] = $rolesById[$roleId];
        }

        usort(
            $memberRoles,
            static fn (array $left, array $right): int => $right['position'] <=> $left['position'],
        );

        return array_values(array_map(static fn (array $role): string => $role['name'], $memberRoles));
    }

    /**
     * @return array<mixed>|null
     */
    private function requestJson(string $url, string $authorization): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Authorization: '.$authorization,
                    'Accept: application/json',
                ],
                'ignore_errors' => true,
                'timeout' => 4,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if (!is_string($response) || '' === $response) {
            return null;
        }

        $data = json_decode($response, true);

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<string, mixed>|null $response
     */
    private function responseHasRole(?array $response, string $roleId): bool
    {
        if (!$response || !isset($response['roles']) || !is_array($response['roles'])) {
            return false;
        }

        return in_array($roleId, array_map('strval', $response['roles']), true);
    }

    private function readEnv(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if (false === $value || null === $value || '' === $value) {
            return null;
        }

        return trim((string) $value);
    }

    private function normalizeBotToken(string $token): string
    {
        return trim((string) preg_replace('/^Bot\s+/i', '', $token));
    }
}
