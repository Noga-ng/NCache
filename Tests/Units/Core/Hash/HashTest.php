<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Core\Hash;

use NCache\Core\Hash;
use NCache\Tests\TestsUnit\TestsUnit;

final class HashTest extends TestsUnit
{
    public function testItGeneratesTheExpectedDefaultHash(): void
    {
        $hash = new Hash('noga');

        self::assertSame(
            hash('xxh128', 'noga'),
            $hash->get()
        );
    }

    public function testSameValueGeneratesSameHash(): void
    {
        $firstHash = (new Hash('noga'))->get();
        $secondHash = (new Hash('noga'))->get();

        self::assertSame($firstHash, $secondHash);
    }

    public function testDifferentValuesGenerateDifferentHashes(): void
    {
        $firstHash = (new Hash('noga'))->get();
        $secondHash = (new Hash('germainio'))->get();

        self::assertNotSame($firstHash, $secondHash);
    }

    public function testItCanHashAnArray(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Noga',
        ];

        $hash = new Hash($data);

        self::assertSame(
            hash('xxh128', serialize($data)),
            $hash->get()
        );
    }

    public function testSameArrayGeneratesSameHash(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Noga',
        ];

        $firstHash = (new Hash($data))->get();
        $secondHash = (new Hash($data))->get();

        self::assertSame($firstHash, $secondHash);
    }

    public function testItAcceptsACustomHashAlgorithm(): void
    {
        $hash = new Hash('noga', 'sha256');

        self::assertSame(
            hash('sha256', 'noga'),
            $hash->get()
        );
    }

    public function testSha256ProducesA64CharacterHash(): void
    {
        $hash = new Hash('noga', 'sha256');

        self::assertSame(64, strlen($hash->get()));
    }
}