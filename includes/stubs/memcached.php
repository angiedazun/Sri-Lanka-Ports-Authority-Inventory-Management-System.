<?php
/**
 * Memcached Extension Stub
 * Prevents IDE warnings when Memcached extension is not installed
 * This file is only loaded when the actual Memcached extension is unavailable
 */

if (!extension_loaded('memcached')) {
    class Memcached {
        // Result codes
        const RES_SUCCESS = 0;
        const RES_FAILURE = 1;
        const RES_HOST_LOOKUP_FAILURE = 2;
        const RES_UNKNOWN_READ_FAILURE = 7;
        const RES_PROTOCOL_ERROR = 8;
        const RES_CLIENT_ERROR = 9;
        const RES_SERVER_ERROR = 10;
        const RES_WRITE_FAILURE = 5;
        const RES_DATA_EXISTS = 12;
        const RES_NOTSTORED = 14;
        const RES_NOTFOUND = 16;
        const RES_PARTIAL_READ = 18;
        const RES_SOME_ERRORS = 19;
        const RES_NO_SERVERS = 20;
        const RES_END = 21;
        const RES_ERRNO = 26;
        const RES_BUFFERED = 32;
        const RES_TIMEOUT = 31;
        const RES_BAD_KEY_PROVIDED = 33;
        const RES_CONNECTION_SOCKET_CREATE_FAILURE = 11;
        
        // Options
        const OPT_COMPRESSION = -1001;
        const OPT_SERIALIZER = -1003;
        const OPT_PREFIX_KEY = -1002;
        const OPT_HASH = 2;
        const OPT_DISTRIBUTION = 9;
        const OPT_LIBKETAMA_COMPATIBLE = 16;
        const OPT_BUFFER_WRITES = 10;
        const OPT_BINARY_PROTOCOL = 18;
        const OPT_NO_BLOCK = 0;
        const OPT_TCP_NODELAY = 1;
        const OPT_SOCKET_SEND_SIZE = 4;
        const OPT_SOCKET_RECV_SIZE = 5;
        const OPT_CONNECT_TIMEOUT = 14;
        const OPT_RETRY_TIMEOUT = 15;
        const OPT_SEND_TIMEOUT = 19;
        const OPT_RECV_TIMEOUT = 20;
        const OPT_POLL_TIMEOUT = 8;
        const OPT_CACHE_LOOKUPS = 6;
        const OPT_SERVER_FAILURE_LIMIT = 21;
        
        // Serializers
        const SERIALIZER_PHP = 1;
        const SERIALIZER_IGBINARY = 2;
        const SERIALIZER_JSON = 3;
        
        // Hash algorithms
        const HASH_DEFAULT = 0;
        const HASH_MD5 = 1;
        const HASH_CRC = 2;
        const HASH_FNV1_64 = 3;
        const HASH_FNV1A_64 = 4;
        const HASH_FNV1_32 = 5;
        const HASH_FNV1A_32 = 6;
        const HASH_HSIEH = 7;
        const HASH_MURMUR = 8;
        
        // Distribution
        const DISTRIBUTION_MODULA = 0;
        const DISTRIBUTION_CONSISTENT = 1;
        
        private $lastResultCode = self::RES_FAILURE;
        
        public function __construct($persistent_id = null) {}
        
        public function addServer($host, $port, $weight = 0): bool {
            return false;
        }
        
        public function addServers(array $servers): bool {
            return false;
        }
        
        public function get($key, $cache_cb = null, $get_flags = 0) {
            $this->lastResultCode = self::RES_NOTFOUND;
            return false;
        }
        
        public function getMulti(array $keys, $get_flags = 0) {
            $this->lastResultCode = self::RES_NOTFOUND;
            return false;
        }
        
        public function set($key, $value, $expiration = 0): bool {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function setMulti(array $items, $expiration = 0): bool {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function add($key, $value, $expiration = 0): bool {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function replace($key, $value, $expiration = 0): bool {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function delete($key, $time = 0): bool {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function deleteMulti(array $keys, $time = 0): array {
            return [];
        }
        
        public function increment($key, $offset = 1): int {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function decrement($key, $offset = 1): int {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function flush($delay = 0): bool {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function getResultCode(): int {
            return $this->lastResultCode;
        }
        
        public function getResultMessage(): string {
            return 'Memcached extension not loaded';
        }
        
        public function getStats(): array {
            return [];
        }
        
        public function getVersion(): array {
            return [];
        }
        
        public function getAllKeys(): array {
            return [];
        }
        
        public function setOption($option, $value): bool {
            return false;
        }
        
        public function getOption($option) {
            return false;
        }
        
        public function quit(): bool {
            return true;
        }
        
        public function touch($key, $expiration): bool {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function cas($cas_token, $key, $value, $expiration = 0): bool {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function append($key, $value): bool {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function prepend($key, $value): bool {
            $this->lastResultCode = self::RES_FAILURE;
            return false;
        }
        
        public function getServerList(): array {
            return [];
        }
        
        public function resetServerList(): bool {
            return true;
        }
        
        public function getServerByKey($server_key): array {
            return [];
        }
        
        public function isPersistent(): bool {
            return false;
        }
        
        public function isPristine(): bool {
            return true;
        }
    }
}
