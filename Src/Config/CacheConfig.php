<?php

declare(strict_types=1);

namespace NCache\Config;

use NCache\Contract\ConfigInterface;
use NCache\Core\Clock\Duration;
use NCache\Core\Files\ReadFile;
use NCache\Enum\CType;
use NCache\Exceptions\UnexpectedConfigException;

/**
 * @phpstan-type RedisConfig array{
 *     host:string,
 *     port:int,
 *     timeout:int|float,
 *     password:string|null,
 *     database:int
 * }
 *
 * @phpstan-type MemcachedConfig array{
 *     host:string,
 *     port:int,
 *     weight:int
 * }
 *
 * @phpstan-type Drivers array{
 *     redis?:RedisConfig,
 *     memcached?:MemcachedConfig
 * }
 *
 * @phpstan-type Extensions array<string,string>
 *
 * @phpstan-type Entry array{
 *     cachePath:string,
 *     defaultDriver:string|null,
 *     namespace:string|null,
 *     extensions:Extensions,
 *     defaultTtl:string|int|null,
 *     drivers?:Drivers,
 *     driversFrom?:string
 * }
 *
 * @phpstan-type ResolvedEntry array{
 *     cachePath:string,
 *     defaultDriver:string|null,
 *     namespace:string|null,
 *     extensions:Extensions,
 *     defaultTtl:int|null,
 *     drivers:Drivers
 * }
 *
 * @phpstan-type Config array<string,Entry>
 */
final class CacheConfig implements ConfigInterface
{
    private static ?self $instance = null;
    private string $dirname;
    /** @var Config */
    private array $data = [];
    /** @var ResolvedEntry|null */
    private ?array $current = null;
    private ?string $currentProfile = null;

    private function __construct(
        private readonly string $filename,
    ) {
        $this->dirname = dirname($this->filename);
        $this->load();
    }

    public static function config(?string $filename = null): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (
            $filename === null
            || trim($filename) === ''
        ) {
            throw new UnexpectedConfigException(
                'Configuration file is required.',
            );
        }

        self::$instance = new self(
            $filename,
        );

        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function use(string $profile): static
    {
        if (!isset($this->data[$profile])) {
            throw new UnexpectedConfigException(
                "Undefined cache profile: {$profile}",
            );
        }

        $this->current = $this->resolve(
            $profile,
            $this->data[$profile],
        );

        $this->currentProfile = $profile;

        return $this;
    }

    public function driversFrom(string $profile): static
    {
        if ($this->current === null) {
            throw new UnexpectedConfigException(
                'No cache profile selected.',
            );
        }

        $currentProfile
            = $this->currentProfile;

        if ($currentProfile === null) {
            throw new UnexpectedConfigException(
                'No cache profile selected.',
            );
        }

        $this->current['drivers']
            = $this->resolveDriversFrom(
                $currentProfile,
                $profile,
            );

        return $this;
    }

    /**
     * @param Entry $entry
     * @return ResolvedEntry
     */
    private function resolve(
        string $profile,
        array $entry,
    ): array {
        $drivers
            = $entry['drivers'] ?? [];

        $driversFrom
            = $entry['driversFrom'] ?? null;

        if (
            $driversFrom !== null
            && $driversFrom !== ''
        ) {
            $drivers
                = $this->resolveDriversFrom(
                    $profile,
                    $driversFrom,
                );
        }

        $ttl = $entry['defaultTtl'];
        if (\is_string($ttl)) {
            $ttl = $this->resolveDefaultTtl($ttl);
        }

        return [
            'cachePath'
                => $entry['cachePath'],
            'defaultDriver'
                => $entry['defaultDriver'],
            'namespace'
                => $entry['namespace'],
            'extensions'
                => $entry['extensions'],
            'defaultTtl'
                => $ttl,
            'drivers'
                => $drivers,
        ];
    }

    /**
     * @param array<string,bool> $visited
     * @return Drivers
     */
    private function resolveDriversFrom(
        string $profile,
        string $source,
        array $visited = [],
    ): array {
        if ($profile === $source) {
            throw new UnexpectedConfigException(
                "Profile {$profile} cannot inherit drivers from itself.",
            );
        }

        if (isset($visited[$source])) {
            throw new UnexpectedConfigException(
                "Circular driversFrom reference detected for profile: {$source}",
            );
        }

        if (!isset($this->data[$source])) {
            throw new UnexpectedConfigException(
                "Undefined driver source profile: {$source}",
            );
        }

        $visited[$source] = true;

        $sourceEntry
            = $this->data[$source];

        $next
            = $sourceEntry['driversFrom']
                ?? null;

        if (
            $next !== null
            && $next !== ''
        ) {
            return $this->resolveDriversFrom(
                $source,
                $next,
                $visited,
            );
        }

        return $sourceEntry['drivers']
            ?? [];
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if ($path[0] === '/' || $path[0] === '\\') {
            return true;
        }

        return preg_match(
            '/^[A-Za-z]:[\\\\\\/]/',
            $path,
        ) === 1;
    }

    private function resolveCachePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        $cachePath = preg_replace(
            '#^\.[\\\\/]#',
            '',
            $path,
        ) ?? $path;

        return rtrim(
            $this->dirname,
            '/\\',
        )
            . DIRECTORY_SEPARATOR
            . $cachePath;
    }

    /**
     * @param string|int|null $ttl
     * @throws UnexpectedConfigException
     * @return int|null
     */
    private function resolveDefaultTtl(string|int|null $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (\is_int($ttl)) {
            if ($ttl < 0) {
                throw new UnexpectedConfigException(
                    'Default TTL cannot be negative.',
                );
            }

            return $ttl;
        }

        if (
            preg_match(
                '/^([a-zA-Z]+)\((.*)\)$/',
                trim($ttl),
                $m,
            ) !== 1
        ) {
            throw new UnexpectedConfigException(
                "Invalid TTL expression: {$ttl}",
            );
        }

        $function = $m[1];

        if (!\in_array(
            $function,
            [
                'month',
                'week',
                'days',
                'hours',
                'minutes',
                'second',
                'make',
            ],
            true,
        )) {
            $class = Duration::class;

            throw new UnexpectedConfigException(
                "Undefined function {$function} in class {$class}.",
            );
        }

        if ($function === 'make') {
            return $this->resolveDurationMake(
                $m[2],
            );
        }

        if (
            preg_match(
                '/^\d+$/',
                trim($m[2]),
            ) !== 1
        ) {
            throw new UnexpectedConfigException(
                "Invalid value {$m[2]} for {$function}().",
            );
        }

        $value = (int) $m[2];

        return match ($function) {
            'month' => Duration::month($value),
            'week' => Duration::week($value),
            'days' => Duration::days($value),
            'hours' => Duration::hours($value),
            'minutes' => Duration::minutes($value),
            'second' => Duration::second($value),
        };
    }

    private function resolveDurationMake(string $values): int
    {
        $parts = array_map(
            'trim',
            explode(',', $values),
        );

        if (\count($parts) > 4) {
            throw new UnexpectedConfigException(
                'Duration::make() accepts at most 4 arguments.',
            );
        }

        foreach ($parts as $value) {
            if (
                $value === ''
                || preg_match('/^\d+$/', $value) !== 1
            ) {
                throw new UnexpectedConfigException(
                    'Duration::make() arguments must be non-negative integers.',
                );
            }
        }

        $days = (int) $parts[0];

        $hours = isset($parts[1])
            ? (int) $parts[1]
            : 0;

        $minutes = isset($parts[2])
            ? (int) $parts[2]
            : 0;

        $seconds = isset($parts[3])
            ? (int) $parts[3]
            : 0;

        return Duration::make(
            $days,
            $hours,
            $minutes,
            $seconds,
        );
    }

    private function load(): void
    {
        if (!is_file($this->filename)) {
            throw new UnexpectedConfigException(
                "Configuration file not found: {$this->filename}",
            );
        }

        if (!is_readable($this->filename)) {
            throw new UnexpectedConfigException(
                "Configuration file is not readable: {$this->filename}",
            );
        }

        $content
            = (new ReadFile(
                $this->filename,
                CType::JSON,
            ))
        ->get();

        if (!\is_array($content)) {
            throw new UnexpectedConfigException(
                'Invalid cache configuration.',
            );
        }

        $this->data
            = $this->normalize(
                $content,
            );
    }

    /**
     * @param array<array-key,mixed> $content
     * @return Config
     */
    private function normalize(array $content): array
    {
        if ($content === []) {
            throw new UnexpectedConfigException(
                'Cache configuration cannot be empty.',
            );
        }

        $config = [];

        foreach ($content as $profile => $entry) {
            if (!\is_string($profile)) {
                throw new UnexpectedConfigException(
                    'Configuration profile na me must be a string.',
                );
            }

            if (!\is_array($entry)) {
                throw new UnexpectedConfigException(
                    "Invalid configuration profile: {$profile}",
                );
            }

            $config[$profile]
                = $this->normalizeEntry(
                    $profile,
                    $entry,
                );
        }

        return $config;
    }

    /**
     * @param array<array-key,mixed> $entry
     * @return Entry
     */
    private function normalizeEntry(string $profile, array $entry): array
    {
        $cachePath = $entry['cachePath'] ?? null;

        if (
            !\is_string($cachePath)
            || trim($cachePath) === ''
        ) {
            throw new UnexpectedConfigException(
                "Profile {$profile} requires a valid cachePath.",
            );
        }

        $cachePath = $this->resolveCachePath($cachePath);

        $defaultDriver
            = $entry['defaultDriver']
                ?? null;

        if (
            $defaultDriver !== null
            && !\is_string($defaultDriver)
        ) {
            throw new UnexpectedConfigException(
                "Invalid defaultDriver in profile {$profile}.",
            );
        }

        if (
            $defaultDriver !== null
            && !\in_array(
                $defaultDriver,
                ['SERIALIZE', 'JSON', 'STRING', 'REDIS', 'MEMCACHED', 'SQLite'],
                true,
            )
        ) {
            throw new UnexpectedConfigException(
                "Undefined defaultDriver {$defaultDriver} in profile {$profile}.",
            );
        }

        $namespace
            = $entry['namespace']
                ?? null;

        if (
            $namespace !== null
            && !\is_string($namespace)
        ) {
            throw new UnexpectedConfigException(
                "Invalid namespace in profile {$profile}.",
            );
        }

        $defaultTtl = $entry['defaultTtl'] ?? null;

        if (
            $defaultTtl !== null
            && !\is_int($defaultTtl)
            && !\is_string($defaultTtl)
        ) {
            throw new UnexpectedConfigException(
                "Invalid defaultTtl in profile {$profile}.",
            );
        }

        if (
            \is_int($defaultTtl)
            && $defaultTtl < 0
        ) {
            throw new UnexpectedConfigException(
                "defaultTtl cannot be negative in profile {$profile}.",
            );
        }

        $driversFrom
            = $entry['driversFrom']
                ?? null;

        if (
            $driversFrom !== null
            && !\is_string($driversFrom)
        ) {
            throw new UnexpectedConfigException(
                "Invalid driversFrom in profile {$profile}.",
            );
        }

        $extensions = $this->normalizeExtensions(
            $profile,
            $entry['extensions'] ?? [],
        );

        $drivers = $this->normalizeDrivers(
            $profile,
            $entry['drivers'] ?? [],
        );

        $normalized = [
            'cachePath'
                => $cachePath,
            'defaultDriver'
                => $defaultDriver,
            'namespace'
                => $namespace,
            'extensions'
                => $extensions,
            'defaultTtl'
                => $defaultTtl,
            'drivers'
                => $drivers,
        ];

        if (
            $driversFrom !== null
            && $driversFrom !== ''
        ) {
            $normalized['driversFrom']
                = $driversFrom;
        }

        return $normalized;
    }

    /**
     * @param mixed $extensions
     * @return Extensions
     */
    private function normalizeExtensions(string $profile, mixed $extensions): array
    {
        if (!\is_array($extensions)) {
            throw new UnexpectedConfigException(
                "Invalid extensions in profile {$profile}.",
            );
        }

        $result = [];

        foreach (
            $extensions as $type => $extension
        ) {
            if (!\is_string($type)) {
                throw new UnexpectedConfigException(
                    "Invalid extension type in profile {$profile}.",
                );
            }

            if (
                !\is_string($extension)
                || trim($extension) === ''
            ) {
                throw new UnexpectedConfigException(
                    "Invalid extension for {$type} in profile {$profile}.",
                );
            }

            $result[$type]
                = $extension;
        }

        return $result;
    }

    /**
     * @param mixed $drivers
     * @return Drivers
     */
    private function normalizeDrivers(string $profile, mixed $drivers): array
    {
        if (!\is_array($drivers)) {
            throw new UnexpectedConfigException(
                "Invalid drivers in profile {$profile}.",
            );
        }

        $result = [];

        if (\array_key_exists(
            'redis',
            $drivers,
        )) {
            $redis
                = $drivers['redis'];

            if (!\is_array($redis)) {
                throw new UnexpectedConfigException(
                    "Invalid Redis configuration in profile {$profile}.",
                );
            }

            $result['redis']
                = $this->normalizeRedis(
                    $profile,
                    $redis,
                );
        }

        if (\array_key_exists(
            'memcached',
            $drivers,
        )) {
            $memcached
                = $drivers['memcached'];

            if (!\is_array($memcached)) {
                throw new UnexpectedConfigException(
                    "Invalid Memcached configuration in profile {$profile}.",
                );
            }

            $result['memcached']
                = $this->normalizeMemcached($profile, $memcached);
        }

        return $result;
    }

    /**
     * @param array<array-key,mixed> $redis
     * @return RedisConfig
     */
    private function normalizeRedis(string $profile, array $redis): array
    {
        $host
            = $redis['host']
                ?? null;

        $port
            = $redis['port']
                ?? null;

        $timeout
            = $redis['timeout']
                ?? 0;

        $password
            = $redis['password']
                ?? null;

        $database = $redis['database'] ?? 0;

        if (
            !\is_string($host)
            || trim($host) === ''
        ) {
            throw new UnexpectedConfigException(
                "Invalid Redis host in profile {$profile}.",
            );
        }

        if (
            !\is_int($port)
            || $port <= 0
            || $port > 65535
        ) {
            throw new UnexpectedConfigException(
                "Invalid Redis port in profile {$profile}.",
            );
        }

        if (
            !\is_int($timeout)
            && !\is_float($timeout)
        ) {
            throw new UnexpectedConfigException(
                "Invalid Redis timeout in profile {$profile}.",
            );
        }

        if ($timeout < 0) {
            throw new UnexpectedConfigException(
                "Redis timeout cannot be negative in profile {$profile}.",
            );
        }

        if (
            $password !== null
            && !\is_string($password)
        ) {
            throw new UnexpectedConfigException(
                "Invalid Redis password in profile {$profile}.",
            );
        }

        if (
            !\is_int($database)
            || $database < 0
        ) {
            throw new UnexpectedConfigException(
                "Invalid Redis database in profile {$profile}.",
            );
        }

        return [
            'host' => $host,
            'port' => $port,
            'timeout' => $timeout,
            'password' => $password,
            'database' => $database,
        ];
    }

    /**
     * @param array<array-key,mixed> $memcached
     * @return MemcachedConfig
     */
    private function normalizeMemcached(string $profile, array $memcached): array
    {
        $host
            = $memcached['host']
                ?? null;

        $port
            = $memcached['port']
                ?? null;

        $weight
            = $memcached['weight']
                ?? 0;

        if (
            !\is_string($host)
            || trim($host) === ''
        ) {
            throw new UnexpectedConfigException(
                "Invalid Memcached host in profile {$profile}.",
            );
        }

        if (
            !\is_int($port)
            || $port <= 0
            || $port > 65535
        ) {
            throw new UnexpectedConfigException(
                "Invalid Memcached port in profile {$profile}.",
            );
        }

        if (!\is_int($weight)) {
            throw new UnexpectedConfigException(
                "Invalid Memcached weight in profile {$profile}.",
            );
        }

        if ($weight < 0) {
            throw new UnexpectedConfigException(
                "Memcached weight cannot be negative in profile {$profile}.",
            );
        }

        return [
            'host' => $host,
            'port' => $port,
            'weight' => $weight,
        ];
    }

    /**
     * @return ResolvedEntry
     */
    private function active(): array
    {
        if ($this->current === null) {
            throw new UnexpectedConfigException(
                'No cache profile selected.',
            );
        }

        return $this->current;
    }

    public function profile(): string
    {
        if ($this->currentProfile === null) {
            throw new UnexpectedConfigException(
                'No cache profile selected.',
            );
        }

        return $this->currentProfile;
    }

    public function getBasePath(): string
    {
        return $this->active()['cachePath'];
    }

    public function getDefaultDriver(): ?CType
    {
        $defaultDriver = $this->active()['defaultDriver'];
        return match ($defaultDriver) {
            'SERIALIZE' => CType::SERIALIZE,
            'JSON' => CType::JSON,
            'STRING' => CType::STRING,
            'REDIS' => CType::REDIS,
            'MEMCACHED' => CType::MEMCACHED,
            'SQLite' => CType::SQLite,
            default => null
        };
    }

    public function getNamespace(): ?string
    {
        return $this
            ->active()['namespace'];
    }

    public function getDefaultTtl(): ?int
    {
        return $this
            ->active()['defaultTtl'];
    }

    /**
     * @return Extensions
     */
    public function getExtensions(): array
    {
        return $this
            ->active()['extensions'];
    }

    public function getExtension(
        CType $type,
    ): ?string {
        return $this
            ->active()['extensions'][$type->name]
                ?? null;
    }

    /**
     * @return Drivers
     */
    public function getDrivers(): array
    {
        return $this
            ->active()['drivers'];
    }

    /**
     * @return RedisConfig|null
     */
    public function getRedis(): ?array
    {
        return $this
            ->active()['drivers']['redis']
                ?? null;
    }

    /**
     * @return MemcachedConfig|null
     */
    public function getMemcached(): ?array
    {
        return $this
            ->active()['drivers']['memcached']
                ?? null;
    }

    /**
     * @return ResolvedEntry
     */
    public function getData(): array
    {
        return $this->active();
    }

    /**
     * @return Config
     */
    public function getAll(): array
    {
        return $this->data;
    }
}
