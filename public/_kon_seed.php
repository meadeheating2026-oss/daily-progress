<?php
$secret = getenv('KON_SEED_SECRET');
$given  = $_SERVER['HTTP_X_KON_SEED'] ?? '';
if (!$secret || !$given || !hash_equals($secret, $given)) {
    http_response_code(403);
    exit('Forbidden');
}
set_time_limit(300);
$sql = file_get_contents('php://input');
if (!$sql) {
    http_response_code(400);
    exit('Empty SQL');
}
$sql = preg_replace('/^\/\*M!999999.*?\*\/\s*/s', '', $sql);
$tmp = tempnam(sys_get_temp_dir(), 'seed_');
file_put_contents($tmp, $sql);

$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_DATABASE');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');

$env = array_merge($_ENV, ['MYSQL_PWD' => $pass]);
$bin = trim(shell_exec('command -v mariadb || command -v mysql'));
$cmd = sprintf(
    '%s --ssl=0 -h %s -P %s -u %s %s < %s 2>&1',
    escapeshellcmd($bin),
    escapeshellarg($host),
    escapeshellarg($port),
    escapeshellarg($user),
    escapeshellarg($db),
    escapeshellarg($tmp)
);
$proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
$out  = stream_get_contents($pipes[1]);
$err  = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$code = proc_close($proc);
@unlink($tmp);

if ($code !== 0) {
    // Retry without --ssl=0 in case client is Oracle mysql (--ssl-mode=DISABLED)
    file_put_contents($tmp, $sql);
    $cmd2 = sprintf(
        '%s --ssl-mode=DISABLED -h %s -P %s -u %s %s < %s 2>&1',
        escapeshellcmd($bin),
        escapeshellarg($host),
        escapeshellarg($port),
        escapeshellarg($user),
        escapeshellarg($db),
        escapeshellarg($tmp)
    );
    $proc2 = proc_open($cmd2, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes2, null, $env);
    $out   = stream_get_contents($pipes2[1]);
    $err   = stream_get_contents($pipes2[2]);
    fclose($pipes2[1]);
    fclose($pipes2[2]);
    $code  = proc_close($proc2);
    @unlink($tmp);
}

$pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass);
$tables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($db))->fetchColumn();
$users  = $tables > 0 ? (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() : 0;

header('Content-Type: application/json');
echo json_encode(['code' => $code, 'tables' => $tables, 'users' => $users, 'out' => $out, 'err' => $err]);
