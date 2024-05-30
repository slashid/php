<?php

namespace SlashId\Test\Php\Abstraction;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SlashId\Php\Abstraction\TokenAbstraction;
use SlashId\Php\Exception\MalformedTokenException;
use SlashId\Php\SlashIdSdk;

/**
 * @covers \SlashId\Php\Abstraction\TokenAbstraction
 */
class TokenAbstractionTest extends TestCase
{
    protected TokenAbstraction $token;
    protected SlashIdSdk&MockObject $sdk;

    public function setUp(): void
    {
        $this->token = new TokenAbstraction(
            $this->sdk = $this->createMock(SlashIdSdk::class),
        );
    }

    public static function dataProviderTestValidateToken(): array
    {
        return [[false], [true]];
    }

    /**
     * @dataProvider dataProviderTestValidateToken
     */
    public function testValidateToken(bool $valid): void
    {
        $this->sdk
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->identicalTo('/token/validate'),
                $this->identicalTo(['token' => 'aaaaa'])
            )
            ->willReturn(['valid' => $valid])
        ;

        $response = $this->token->validateToken('aaaaa');
        $this->assertEquals($valid, $response);
    }

    public static function dataProviderTestGetSubFromToken(): array
    {
        return [
            ['aaaa', 'The token is malformed.'],
            ['aaaa.aaaa.aaaa.aaaa', 'The token is malformed.'],
            ['aaaa.' . base64_encode(json_encode(['sub' => '9999-9999-9999'])) . '.aaaa', null],
        ];
    }

    /**
     * @dataProvider dataProviderTestGetSubFromToken
     */
    public function testGetSubFromToken(string $token, ?string $expectedExceptionMessage): void
    {
        if ($expectedExceptionMessage) {
            $this->expectException(MalformedTokenException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $this->assertEquals(
            '9999-9999-9999',
            $this->token->getSubFromToken($token),
        );
    }
}
