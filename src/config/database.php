<?php
/**
 * Logi-Prime — PDO Singleton
 */

class Database {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_PORT, DB_NAME
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    throw $e;
                }
                http_response_code(500);
                die('Erro de conexao com o banco de dados.');
            }
        }
        return self::$instance;
    }

    // Impedir clonagem e unserialization
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception('Nao e permitido deserializar um singleton.');
    }
}

/**
 * Atalho global
 */
function db(): PDO {
    return Database::get();
}
