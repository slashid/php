<?php

namespace SlashId\Php\Abstraction;

use SlashId\Php\Exception\MalformedTokenException;

class TokenAbstraction extends AbstractionBase
{
    public function validateToken(string $token): bool
    {
        return $this->sdk->post('/token/validate', ['token' => $token])['valid'] ?? false;
    }

    public function getSubFromToken(string $token): string
    {
        if (!str_contains($token, '.')) {
            throw new MalformedTokenException('The token is malformed.');
        }

        $tokenParts = explode('.', $token);

        if (count($tokenParts) !== 3) {
            throw new MalformedTokenException('The token is malformed.');
        }

        [, $userDataTokenPart] = $tokenParts;
        $userData = json_decode(base64_decode($userDataTokenPart), true);
        return $userData['sub'];
    }
}
