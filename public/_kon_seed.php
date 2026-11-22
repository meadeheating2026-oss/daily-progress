<?php
$secret = getenv('KON_SEED_SECRET');
$given  = $_SERVER['HTTP_X_KON_SEED'] ?? '';
if (!$secret || !$given || !hash_equals($secret, $given)) {
    http_response_code(403);
    exit('Forbidden');
}
set_time_limit(300);
ini_set('memory_limit', '256M');

$raw = file_get_contents('php://input');
if (!$raw) {
    http_response_code(400);
    exit('Empty SQL');
}

$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_DATABASE');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0; SET UNIQUE_CHECKS=0; SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';");

    $lines = preg_split("/\r\n|\n|\r/", $raw);
    $buffer = '';
    $executed = 0;
    $errors = [];

    foreach ($lines as $line) {
        $trim = ltrim($line);
        if ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '/*M!')) {
            continue;
        }
        $buffer .= $line . "\n";
        if (str_ends_with(rtrim($line), ';')) {
            try {
                $pdo->exec($buffer);
                $executed++;
            } catch (Throwable $e) {
                $errors[] = substr($e->getMessage(), 0, 200);
            }
            $buffer = '';
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS=1; SET UNIQUE_CHECKS=1;");
    $tables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($db))->fetchColumn();
    $users  = $tables > 0 ? (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() : 0;

    header('Content-Type: application/json');
    echo json_encode([
        'ok'       => true,
        'executed' => $executed,
        'tables'   => $tables,
        'users'    => $users,
        'errors'   => array_slice($errors, 0, 10),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
