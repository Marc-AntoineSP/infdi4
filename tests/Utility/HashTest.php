<?php

declare(strict_types=1);

namespace Utility;

use App\Utility\Hash;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class HashTest extends TestCase
{
    private string|false $originalSaltCharset;

    private bool $hadOriginalEnvSaltCharset;

    private mixed $originalEnvSaltCharset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalSaltCharset = getenv('SALT_CHARSET');
        $this->hadOriginalEnvSaltCharset = array_key_exists('SALT_CHARSET', $_ENV);
        $this->originalEnvSaltCharset = $this->hadOriginalEnvSaltCharset
            ? $_ENV['SALT_CHARSET']
            : null;
    }

    protected function tearDown(): void
    {
        if ($this->originalSaltCharset === false) {
            putenv('SALT_CHARSET');
        } else {
            putenv('SALT_CHARSET=' . $this->originalSaltCharset);
        }

        if ($this->hadOriginalEnvSaltCharset) {
            $_ENV['SALT_CHARSET'] = $this->originalEnvSaltCharset;
        } else {
            unset($_ENV['SALT_CHARSET']);
        }

        parent::tearDown();
    }

    public function testGenerateShouldHashWithoutSalt(): void
    {
        $input = 'my-password';

        $this->assertSame(
            hash('sha256', $input),
            Hash::generate($input)
        );
    }

    public function testGenerateShouldHashWithSalt(): void
    {
        $input = 'my-password';
        $salt = 'my-salt';

        $this->assertSame(
            hash('sha256', $input . $salt),
            Hash::generate($input, $salt)
        );
    }

    public function testGenerateSaltShouldGenerateRandomSaltWithExpectedLength(): void
    {
        $this->setSaltCharset('abc123');

        $salt = Hash::generateSalt(32);

        $this->assertSame(32, strlen($salt));
        $this->assertMatchesRegularExpression('/^[abc123]+$/', $salt);
        $this->assertNotSame(Hash::generateSalt(32), Hash::generateSalt(32));
    }

    public function testGenerateSaltShouldUseEnvFallbackWhenGetenvIsMissing(): void
    {
        putenv('SALT_CHARSET');
        $_ENV['SALT_CHARSET'] = 'wxyz';

        $salt = Hash::generateSalt(8);

        $this->assertSame(8, strlen($salt));
        $this->assertMatchesRegularExpression('/^[wxyz]+$/', $salt);
    }

    public function testGenerateSaltShouldThrowForZeroLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Salt length must be a positive integer.');

        Hash::generateSalt(0);
    }

    public function testGenerateSaltShouldThrowForNegativeLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Salt length must be a positive integer.');

        Hash::generateSalt(-1);
    }

    public function testGenerateSaltShouldThrowWhenCharsetIsMissing(): void
    {
        putenv('SALT_CHARSET');
        unset($_ENV['SALT_CHARSET']);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Missing required env var SALT_CHARSET in PHP environment.');

        Hash::generateSalt(8);
    }

    public function testGenerateSaltShouldThrowWhenCharsetHasLessThanTwoCharacters(): void
    {
        $this->setSaltCharset('a');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('SALT_CHARSET must contain at least 2 characters.');

        Hash::generateSalt(8);
    }

    public function testGenerateUniqueShouldReturnSha256String(): void
    {
        $uniqueHashA = Hash::generateUnique();
        $uniqueHashB = Hash::generateUnique();

        $this->assertSame(64, strlen($uniqueHashA));
        $this->assertSame(64, strlen($uniqueHashB));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $uniqueHashA);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $uniqueHashB);
        $this->assertNotSame($uniqueHashA, $uniqueHashB);
    }

    private function setSaltCharset(string $charset): void
    {
        putenv('SALT_CHARSET=' . $charset);
        $_ENV['SALT_CHARSET'] = $charset;
    }
}
