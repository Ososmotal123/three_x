<?php

if (isset($conn) && $conn instanceof mysqli) {
    return $conn;
}

$config = require __DIR__ . '/config.php';

if (!is_array($config) || !isset($config['db'])) {
    db_connection_error('Database configuration is missing.');
}

$db = $config['db'];
$host = $db['host'] ?? 'localhost';
$username = $db['username'] ?? 'root';
$password = $db['password'] ?? '';
$database = $db['database'] ?? '';
$port = $db['port'] ?? 3306;
$charset = $db['charset'] ?? 'utf8mb4';

$conn = attempt_connection($host, $username, $password, $database, (int) $port);

if ($conn->connect_errno === 1049 && $database !== '') {
    $conn->close();
    create_database_if_missing($host, $username, $password, $database, (int) $port, $charset);
    $conn = attempt_connection($host, $username, $password, $database, (int) $port);
}

if ($conn->connect_error) {
    db_connection_error('Failed to connect to the database. Please try again later.');
}

if ($charset && !$conn->set_charset($charset)) {
    db_connection_error('Failed to set database charset.');
}

return $conn;

function attempt_connection(string $host, string $username, string $password, string $database, int $port): mysqli
{
    return @new mysqli($host, $username, $password, $database, $port);
}

function create_database_if_missing(
    string $host,
    string $username,
    string $password,
    string $database,
    int $port,
    string $charset
): void {
    $tmp = @new mysqli($host, $username, $password, '', $port);

    if ($tmp->connect_error) {
        db_connection_error('Failed to initialize database connection.');
    }

    $sanitizedName = str_replace('`', '``', $database);
    $charsetClause = $charset ? sprintf(' CHARACTER SET %s', $charset) : '';
    $sql = sprintf('CREATE DATABASE IF NOT EXISTS `%s`%s', $sanitizedName, $charsetClause);

    if (!$tmp->query($sql)) {
        $tmp->close();
        db_connection_error('Failed to prepare the database.');
    }

    $tmp->close();
}

function db_connection_error(string $message, int $status = 500): void
{
    if (PHP_SAPI !== 'cli') {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
    } else {
        fwrite(STDERR, $message . PHP_EOL);
    }

    exit;
}
