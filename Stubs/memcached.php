<?php declare(strict_types=1);

/**
 * Stub IDE / analyse statique pour ext-memcached.
 *
 * Ne pas charger ce fichier à l'exécution lorsque
 * l'extension ext-memcached est active.
 */
class Memcached
{
    /**
     * Libmemcached behavior options.
     */
    public const LIBMEMCACHED_VERSION_HEX = 16781316;

    public const OPT_COMPRESSION = -1001;
    public const OPT_COMPRESSION_TYPE = -1004;
    public const OPT_COMPRESSION_LEVEL = -1007;
    public const OPT_PREFIX_KEY = -1002;
    public const OPT_SERIALIZER = -1003;
    public const OPT_USER_FLAGS = -1006;
    public const OPT_STORE_RETRY_COUNT = -1005;
    public const OPT_ITEM_SIZE_LIMIT = -1008;
    public const HAVE_IGBINARY = 0;
    public const HAVE_ZSTD = 0;
    public const HAVE_JSON = 0;
    public const HAVE_MSGPACK = 0;
    public const HAVE_ENCODING = 0;
    public const HAVE_SESSION = 0;
    public const HAVE_SASL = 0;
    public const OPT_HASH = 2;
    public const HASH_DEFAULT = 0;
    public const HASH_MD5 = 1;
    public const HASH_CRC = 2;
    public const HASH_FNV1_64 = 3;
    public const HASH_FNV1A_64 = 4;
    public const HASH_FNV1_32 = 5;
    public const HASH_FNV1A_32 = 6;
    public const HASH_HSIEH = 7;
    public const HASH_MURMUR = 8;
    public const OPT_DISTRIBUTION = 9;
    public const DISTRIBUTION_MODULA = 0;
    public const DISTRIBUTION_CONSISTENT = 1;
    public const DISTRIBUTION_VIRTUAL_BUCKET = 6;
    public const OPT_LIBKETAMA_COMPATIBLE = 16;
    public const OPT_LIBKETAMA_HASH = 17;
    public const OPT_TCP_KEEPALIVE = 32;
    public const OPT_BUFFER_WRITES = 10;
    public const OPT_BINARY_PROTOCOL = 18;
    public const OPT_NO_BLOCK = 0;
    public const OPT_TCP_NODELAY = 1;
    public const OPT_SOCKET_SEND_SIZE = 4;
    public const OPT_SOCKET_RECV_SIZE = 5;
    public const OPT_CONNECT_TIMEOUT = 14;
    public const OPT_RETRY_TIMEOUT = 15;
    public const OPT_DEAD_TIMEOUT = 36;
    public const OPT_SEND_TIMEOUT = 19;
    public const OPT_RECV_TIMEOUT = 20;
    public const OPT_POLL_TIMEOUT = 8;
    public const OPT_CACHE_LOOKUPS = 6;
    public const OPT_SERVER_FAILURE_LIMIT = 21;
    public const OPT_AUTO_EJECT_HOSTS = 28;
    public const OPT_HASH_WITH_PREFIX_KEY = 25;
    public const OPT_NOREPLY = 26;
    public const OPT_SORT_HOSTS = 12;
    public const OPT_VERIFY_KEY = 13;
    public const OPT_USE_UDP = 27;
    public const OPT_NUMBER_OF_REPLICAS = 29;
    public const OPT_RANDOMIZE_REPLICA_READ = 30;
    public const OPT_REMOVE_FAILED_SERVERS = 35;
    public const OPT_SERVER_TIMEOUT_LIMIT = 37;
    public const RES_SUCCESS = 0;
    public const RES_FAILURE = 1;
    public const RES_HOST_LOOKUP_FAILURE = 2;
    public const RES_CONNECTION_FAILURE = 3;
    public const RES_CONNECTION_BIND_FAILURE = 4;
    public const RES_WRITE_FAILURE = 5;
    public const RES_READ_FAILURE = 6;
    public const RES_UNKNOWN_READ_FAILURE = 7;
    public const RES_PROTOCOL_ERROR = 8;
    public const RES_CLIENT_ERROR = 9;
    public const RES_SERVER_ERROR = 10;
    public const RES_DATA_EXISTS = 12;
    public const RES_DATA_DOES_NOT_EXIST = 13;
    public const RES_NOTSTORED = 14;
    public const RES_STORED = 15;
    public const RES_NOTFOUND = 16;
    public const RES_PARTIAL_READ = 18;
    public const RES_SOME_ERRORS = 19;
    public const RES_NO_SERVERS = 20;
    public const RES_END = 21;
    public const RES_DELETED = 22;
    public const RES_VALUE = 23;
    public const RES_STAT = 24;
    public const RES_ITEM = 25;
    public const RES_ERRNO = 26;
    public const RES_FAIL_UNIX_SOCKET = 27;
    public const RES_NOT_SUPPORTED = 28;
    public const RES_NO_KEY_PROVIDED = 29;
    public const RES_FETCH_NOTFINISHED = 30;
    public const RES_TIMEOUT = 31;
    public const RES_BUFFERED = 32;
    public const RES_BAD_KEY_PROVIDED = 33;
    public const RES_INVALID_HOST_PROTOCOL = 34;
    public const RES_SERVER_MARKED_DEAD = 35;
    public const RES_UNKNOWN_STAT_KEY = 36;
    public const RES_INVALID_ARGUMENTS = 38;
    public const RES_PARSE_ERROR = 43;
    public const RES_PARSE_USER_ERROR = 44;
    public const RES_DEPRECATED = 45;
    public const RES_IN_PROGRESS = 46;
    public const RES_MAXIMUM_RETURN = 49;
    public const RES_MEMORY_ALLOCATION_FAILURE = 17;
    public const RES_CONNECTION_SOCKET_CREATE_FAILURE = 11;
    public const RES_E2BIG = 37;
    public const RES_KEY_TOO_BIG = 39;
    public const RES_SERVER_TEMPORARILY_DISABLED = 47;
    public const RES_SERVER_MEMORY_ALLOCATION_FAILURE = 48;
    public const RES_PAYLOAD_FAILURE = -1001;
    public const SERIALIZER_PHP = 1;
    public const SERIALIZER_IGBINARY = 2;
    public const SERIALIZER_JSON = 3;
    public const SERIALIZER_JSON_ARRAY = 4;
    public const SERIALIZER_MSGPACK = 5;
    public const COMPRESSION_FASTLZ = 2;
    public const COMPRESSION_ZLIB = 1;
    public const COMPRESSION_ZSTD = 3;
    public const GET_PRESERVE_ORDER = 1;
    public const GET_EXTENDED = 2;
    public const GET_ERROR_RETURN_VALUE = 0;

    /*
     * Garde ici les constantes présentes
     * dans ton memcached-api.php original.
     */

    /**
     * @param string|null $persistent_id
     * @param callable|null $callback
     * @param string|null $connection_str
     */
    public function __construct(
        ?string $persistent_id = null,
        ?callable $callback = null,
        ?string $connection_str = null
    ) {}

    public function add(
        string $key,
        mixed $value,
        int $expiration = 0
    ): bool {}

    public function addByKey(
        string $server_key,
        string $key,
        mixed $value,
        int $expiration = 0
    ): bool {}

    public function addServer(
        string $host,
        int $port,
        int $weight = 0
    ): bool {}

    /**
     * @param array<int, array{
     *     0:string,
     *     1:int,
     *     2?:int
     * }> $servers
     */
    public function addServers(
        array $servers
    ): bool {}

    public function append(
        string $key,
        string $value
    ): ?bool {}

    public function appendByKey(
        string $server_key,
        string $key,
        string $value
    ): ?bool {}

    public function cas(
        string|int|float $cas_token,
        string $key,
        mixed $value,
        int $expiration = 0
    ): bool {}

    public function casByKey(
        string|int|float $cas_token,
        string $server_key,
        string $key,
        mixed $value,
        int $expiration = 0
    ): bool {}

    public function decrement(
        string $key,
        int $offset = 1,
        int $initial_value = 0,
        int $expiry = 0
    ): int|false {}

    public function decrementByKey(
        string $server_key,
        string $key,
        int $offset = 1,
        int $initial_value = 0,
        int $expiry = 0
    ): int|false {}

    public function delete(
        string $key,
        int $time = 0
    ): bool {}

    public function deleteByKey(
        string $server_key,
        string $key,
        int $time = 0
    ): bool {}

    /**
     * @param string[] $keys
     * @return array<string, bool>
     */
    public function deleteMulti(
        array $keys,
        int $time = 0
    ): array {}

    /**
     * @param string[] $keys
     * @return array<string, bool>
     */
    public function deleteMultiByKey(
        string $server_key,
        array $keys,
        int $time = 0
    ): array {}

    /**
     * @return array<string, mixed>|false
     */
    public function fetch(): array|false {}

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function fetchAll(): array|false {}

    public function flush(
        int $delay = 0
    ): bool {}

    public function get(
        string $key,
        ?callable $cache_cb = null,
        int $get_flags = 0
    ): mixed {}

    /**
     * @return string[]|false
     */
    public function getAllKeys(): array|false {}

    public function getByKey(
        string $server_key,
        string $key,
        ?callable $cache_cb = null,
        int $get_flags = 0
    ): mixed {}

    /**
     * @param string[] $keys
     */
    public function getDelayed(
        array $keys,
        bool $with_cas = false,
        ?callable $value_cb = null
    ): bool {}

    /**
     * @param string[] $keys
     */
    public function getDelayedByKey(
        string $server_key,
        array $keys,
        bool $with_cas = false,
        ?callable $value_cb = null
    ): bool {}

    /**
     * @param string[] $keys
     * @return array<string, mixed>|false
     */
    public function getMulti(
        array $keys,
        int $get_flags = 0
    ): array|false {}

    /**
     * @param string[] $keys
     * @return array<string, mixed>|false
     */
    public function getMultiByKey(
        string $server_key,
        array $keys,
        int $get_flags = 0
    ): array|false {}

    public function getOption(
        int $option
    ): mixed {}

    public function getResultCode(): int {}

    public function getResultMessage(): string {}

    /**
     * @return array{
     *     host:string,
     *     port:int,
     *     weight:int
     * }|false
     */
    public function getServerByKey(
        string $server_key
    ): array|false {}

    /**
     * @return array<int, array{
     *     host:string,
     *     port:int,
     *     weight:int
     * }>
     */
    public function getServerList(): array {}

    /**
     * @return array<string, mixed>|false
     */
    public function getStats(
        ?string $type = null
    ): array|false {}

    /**
     * @return array<string, string>|false
     */
    public function getVersion(): array|false {}

    public function increment(
        string $key,
        int $offset = 1,
        int $initial_value = 0,
        int $expiry = 0
    ): int|false {}

    public function incrementByKey(
        string $server_key,
        string $key,
        int $offset = 1,
        int $initial_value = 0,
        int $expiry = 0
    ): int|false {}

    public function isPersistent(): bool {}

    public function isPristine(): bool {}

    public function prepend(
        string $key,
        string $value
    ): ?bool {}

    public function prependByKey(
        string $server_key,
        string $key,
        string $value
    ): ?bool {}

    public function quit(): bool {}

    public function replace(
        string $key,
        mixed $value,
        int $expiration = 0
    ): bool {}

    public function replaceByKey(
        string $server_key,
        string $key,
        mixed $value,
        int $expiration = 0
    ): bool {}

    public function resetServerList(): bool {}

    public function set(
        string $key,
        mixed $value,
        int $expiration = 0
    ): bool {}

    public function setByKey(
        string $server_key,
        string $key,
        mixed $value,
        int $expiration = 0
    ): bool {}

    public function setEncodingKey(
        string $key
    ): bool {}

    /**
     * @param array<string, mixed> $items
     */
    public function setMulti(
        array $items,
        int $expiration = 0
    ): bool {}

    /**
     * @param array<string, mixed> $items
     */
    public function setMultiByKey(
        string $server_key,
        array $items,
        int $expiration = 0
    ): bool {}

    public function setOption(
        int $option,
        mixed $value
    ): bool {}

    /**
     * @param array<int, mixed> $options
     */
    public function setOptions(
        array $options
    ): bool {}

    public function setSaslAuthData(
        string $username,
        string $password
    ): bool {}

    public function touch(
        string $key,
        int $expiration = 0
    ): bool {}

    public function touchByKey(
        string $server_key,
        string $key,
        int $expiration = 0
    ): bool {}
}

class MemcachedException extends Exception {}
