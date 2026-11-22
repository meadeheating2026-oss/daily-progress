<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UrcaBackUpController extends Controller
{
    public function index()
    {
        return view('urcaBackUp');
    }

    public function download(Request $request)
    {
        try {
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $pass     = config('database.connections.mysql.password');
            $host     = config('database.connections.mysql.host');
            $port     = config('database.connections.mysql.port', 3306);

            $filename = 'backup_' . Carbon::now()->format('Y-m-d_H-i-s') . '.sql';
            $filepath = storage_path('app/backups/' . $filename);

            if (!file_exists(storage_path('app/backups'))) {
                mkdir(storage_path('app/backups'), 0755, true);
            }

            // محاولة mysqldump أولاً — ابحث عنه في مسارات XAMPP/WAMP الشائعة
            $mysqldumpPaths = [
                'mysqldump',
                'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
                'C:\\wamp\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
                'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
                'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
                'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
            ];

            $mysqldump = null;
            foreach ($mysqldumpPaths as $path) {
                $test = trim(shell_exec('"' . $path . '" --version 2>&1'));
                if ($test && stripos($test, 'Distrib') !== false) {
                    $mysqldump = $path;
                    break;
                }
            }

            if ($mysqldump) {
                // استخدام mysqldump
                $passArg = $pass ? '--password=' . escapeshellarg($pass) : '';
                $command = sprintf(
                    '"%s" --user=%s %s --host=%s --port=%s --single-transaction --routines --triggers %s > %s 2>&1',
                    $mysqldump,
                    escapeshellarg($username),
                    $passArg,
                    escapeshellarg($host),
                    escapeshellarg($port),
                    escapeshellarg($database),
                    escapeshellarg($filepath)
                );
                exec($command, $output, $returnVar);

                if ($returnVar === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                    return response()->download($filepath, $filename, [
                        'Content-Type' => 'application/octet-stream',
                    ])->deleteFileAfterSend(true);
                }
            }

            // Fallback: بناء SQL يدوياً عبر Laravel DB
            $sql = $this->buildSqlDump($database);

            file_put_contents($filepath, $sql);

            return response()->download($filepath, $filename, [
                'Content-Type' => 'application/octet-stream',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function buildSqlDump(string $database): string
    {
        $sql  = "-- URCA Backup\n";
        $sql .= "-- Generated: " . Carbon::now()->toDateTimeString() . "\n";
        $sql .= "-- Database: `{$database}`\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET NAMES utf8mb4;\n\n";

        $tables = DB::select('SHOW TABLES');
        $key    = 'Tables_in_' . $database;

        foreach ($tables as $tableObj) {
            $table = $tableObj->$key;

            // DROP + CREATE TABLE
            $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
            $createSql    = $createResult[0]->{'Create Table'};

            $sql .= "-- ----------------------------\n";
            $sql .= "-- Table: `{$table}`\n";
            $sql .= "-- ----------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createSql . ";\n\n";

            // INSERT rows — جلب كل الداتا
            $rows = DB::table($table)->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $sql .= "-- Data for `{$table}`\n";

            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $cols     = array_map(function ($col) {
                    return "`{$col}`";
                }, array_keys($rowArray));

                $vals = array_map(function ($val) {
                    if (is_null($val)) {
                        return 'NULL';
                    }
                    return "'" . str_replace(
                        ["\\", "'", "\n", "\r", "\x00", "\x1a"],
                        ["\\\\", "\\'", "\\n", "\\r", "\\0", "\\Z"],
                        (string) $val
                    ) . "'";
                }, array_values($rowArray));

                $sql .= "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
            }

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }
}
