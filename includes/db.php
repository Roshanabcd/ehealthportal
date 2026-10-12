<?php
require_once __DIR__ . '/../config/config.php';

/** Returns a shared PDO connection (prepared statements everywhere = SQL-injection safe). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET time_zone = '+05:45'");
        } catch (PDOException $e) {
            http_response_code(500);
            die('<div style="font-family:sans-serif;max-width:600px;margin:60px auto;padding:20px;border:1px solid #f5c2c7;background:#f8d7da;border-radius:8px">'
              . '<h3>Database connection failed</h3><p>Please make sure <b>MySQL is running in XAMPP</b> and that you imported '
              . '<code>database/ehealth_db.sql</code>. Check the credentials in <code>config/config.php</code>.</p></div>');
        }
    }
    return $pdo;
}

/** Run a query and return the statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function fetch_all(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function fetch_one(string $sql, array $params = []): ?array { $r = q($sql, $params)->fetch(); return $r ?: null; }
function fetch_val(string $sql, array $params = []) { return q($sql, $params)->fetchColumn(); }
