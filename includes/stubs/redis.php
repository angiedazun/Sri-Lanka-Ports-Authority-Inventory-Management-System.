<?php
/**
 * Redis Extension Stub
 * Prevents IDE warnings when Redis extension is not installed
 * This file is only loaded when the actual Redis extension is unavailable
 */

if (!extension_loaded('redis')) {
    class Redis {
        const SERIALIZER_NONE = 0;
        const SERIALIZER_PHP = 1;
        const SERIALIZER_IGBINARY = 2;
        const SERIALIZER_MSGPACK = 3;
        const SERIALIZER_JSON = 4;
        
        const OPT_SERIALIZER = 1;
        const OPT_PREFIX = 2;
        const OPT_READ_TIMEOUT = 3;
        const OPT_SCAN = 4;
        
        public function __construct() {}
        
        public function connect($host, $port = 6379, $timeout = 0.0, $reserved = null, $retry_interval = 0, $read_timeout = 0.0): bool {
            return false;
        }
        
        public function pconnect($host, $port = 6379, $timeout = 0.0, $persistent_id = null, $retry_interval = 0, $read_timeout = 0.0): bool {
            return false;
        }
        
        public function close(): bool {
            return true;
        }
        
        public function setOption($option, $value): bool {
            return false;
        }
        
        public function getOption($option) {
            return null;
        }
        
        public function ping($message = null) {
            return false;
        }
        
        public function get($key) {
            return false;
        }
        
        public function set($key, $value, $timeout = null): bool {
            return false;
        }
        
        public function setex($key, $ttl, $value): bool {
            return false;
        }
        
        public function setnx($key, $value): bool {
            return false;
        }
        
        public function del(...$keys): int {
            return 0;
        }
        
        public function delete(...$keys): int {
            return 0;
        }
        
        public function exists(...$keys): int {
            return 0;
        }
        
        public function incr($key): int {
            return 0;
        }
        
        public function incrBy($key, $value): int {
            return 0;
        }
        
        public function incrByFloat($key, $increment): float {
            return 0.0;
        }
        
        public function decr($key): int {
            return 0;
        }
        
        public function decrBy($key, $value): int {
            return 0;
        }
        
        public function ttl($key): int {
            return -2;
        }
        
        public function expire($key, $ttl): bool {
            return false;
        }
        
        public function expireAt($key, $timestamp): bool {
            return false;
        }
        
        public function keys($pattern): array {
            return [];
        }
        
        public function scan(&$iterator, $pattern = null, $count = 0): array {
            return [];
        }
        
        public function flushDB($async = false): bool {
            return false;
        }
        
        public function flushAll($async = false): bool {
            return false;
        }
        
        public function dbSize(): int {
            return 0;
        }
        
        public function info($option = null) {
            return [];
        }
        
        public function hSet($key, $hashKey, $value): int {
            return 0;
        }
        
        public function hGet($key, $hashKey) {
            return false;
        }
        
        public function hGetAll($key): array {
            return [];
        }
        
        public function hDel($key, ...$hashKeys): int {
            return 0;
        }
        
        public function hExists($key, $hashKey): bool {
            return false;
        }
        
        public function hLen($key): int {
            return 0;
        }
        
        public function lPush($key, ...$values): int {
            return 0;
        }
        
        public function rPush($key, ...$values): int {
            return 0;
        }
        
        public function lPop($key) {
            return false;
        }
        
        public function rPop($key) {
            return false;
        }
        
        public function lLen($key): int {
            return 0;
        }
        
        public function lRange($key, $start, $end): array {
            return [];
        }
        
        public function sAdd($key, ...$values): int {
            return 0;
        }
        
        public function sRem($key, ...$members): int {
            return 0;
        }
        
        public function sMembers($key): array {
            return [];
        }
        
        public function sIsMember($key, $value): bool {
            return false;
        }
        
        public function sCard($key): int {
            return 0;
        }
        
        public function zAdd($key, ...$args): int {
            return 0;
        }
        
        public function zRem($key, ...$members): int {
            return 0;
        }
        
        public function zRange($key, $start, $end, $withscores = false): array {
            return [];
        }
        
        public function zCard($key): int {
            return 0;
        }
        
        public function multi($mode = null) {
            return $this;
        }
        
        public function exec() {
            return [];
        }
        
        public function discard(): bool {
            return false;
        }
        
        public function watch(...$keys): bool {
            return false;
        }
        
        public function unwatch(): bool {
            return false;
        }
        
        public function publish($channel, $message): int {
            return 0;
        }
        
        public function subscribe($channels, $callback) {
            return null;
        }
        
        public function psubscribe($patterns, $callback) {
            return null;
        }
    }
}
