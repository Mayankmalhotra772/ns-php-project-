<?php
/**
 * Database configuration and connection singleton.
 * Uses PDO with PostgreSQL prepared statements only.
 */

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '5432';
            $name = getenv('DB_NAME') ?: 'transactiwar';
            $user = getenv('DB_USER') ?: 'twuser';
            $pass = getenv('DB_PASS') ?: 'tw_s3cur3_p@ss!';

            $dsn = "pgsql:host={$host};port={$port};dbname={$name}";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Use real prepared statements
            ]);
        }
        return self::$instance;
    }

    // Prevent cloning and unserialization
    private function __construct() {}
    private function __clone() {}
    public function __wakeup() { throw new \Exception("Cannot unserialize singleton"); }
}
