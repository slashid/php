<?php

namespace SlashId\Php\Abstraction;

use SlashId\Php\Exception\MalformedTokenException;

class TokenAbstraction extends AbstractionBase
{
    public function validateToken(string $token): bool
    {
        /** @var bool[] */
        $response = $this->sdk->post('/token/validate', ['token' => $token]);

        return $response['valid'] ?? false;
    }

    public function getSubFromToken(string $token): string
    {
        if (!str_contains($token, '.')) {
            throw new MalformedTokenException('The token is malformed.');
        }

        $tokenParts = explode('.', $token);

        if (3 !== count($tokenParts)) {
            throw new MalformedTokenException('The token is malformed.');
        }

        [, $userDataTokenPart] = $tokenParts;
        /** @var string[] */
        $userData = json_decode(base64_decode($userDataTokenPart), true);

        return $userData['sub'];
    }
}
