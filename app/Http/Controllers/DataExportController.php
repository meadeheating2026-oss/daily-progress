<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class DataExportController extends Controller
{
    // 🟢 1- Export كل الجداول كـ JSON جوه ZIP
    public function exportAllData()
    {
        try {
            $zipFileName = 'erp_data_export_' . date('Y-m-d_H-i-s') . '.zip';
            $zipDir = storage_path('app/temp');

            if (!file_exists($zipDir)) {
                mkdir($zipDir, 0777, true);
            }

            $zipFilePath = $zipDir . '/' . $zipFileName;

            $zip = new ZipArchive;
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                Log::error("❌ فشل في فتح ملف ZIP: {$zipFilePath}");
                return response()->json(['error' => 'فشل في إنشاء ملف ZIP'], 500);
            }

            // 1- هات كل أسماء الجداول
            $tables = DB::select("SHOW TABLES");
            $dbName = env('DB_DATABASE');

            foreach ($tables as $table) {
                $tableName = $table->{"Tables_in_$dbName"};

                // استبعاد جداول لارافيل الافتراضية
                if (in_array($tableName, ['migrations', 'failed_jobs', 'password_reset_tokens'])) {
                    continue;
                }

                // 2- هات بيانات الجدول
                $rows = DB::table($tableName)->get();

                // 3- حوّلها JSON
                $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                // 4- أضفها للـ ZIP
                if ($json !== false) {
                    $zip->addFromString($tableName . '.json', $json);
                    Log::info("✅ Added table: {$tableName} (" . count($rows) . " rows)");
                } else {
                    Log::warning("⚠️ Failed to encode table: {$tableName}");
                }
            }

            $zip->close();
            clearstatcache();

            // ✅ تأكيد حجم الملف
            if (!file_exists($zipFilePath) || filesize($zipFilePath) === 0) {
                Log::error("❌ الملف اتولد بس فاضي أو مش موجود: {$zipFilePath}");
                return response()->json(['error' => 'الملف فاضي أو مش موجود'], 500);
            }

            Log::info("📦 الملف اتولد بنجاح: {$zipFilePath}, الحجم: " . filesize($zipFilePath));

            return response()->download($zipFilePath, $zipFileName, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error("❌ ExportAllData Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // 🟢 2- Export كـ SQL Dump باستخدام mysqldump
    public function exportSqlDump()
    {
        try {
            $filename = 'erp_database_' . date('Y-m-d_H-i-s') . '.sql';
            $dir = storage_path('app/temp');

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $filePath = $dir . '/' . $filename;

            $dbHost = config('database.connections.mysql.host');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            // 🔑 تأكدي إن المسار ده صح عندك
            $mysqldumpPath = 'C:\xampp\mysql\bin\mysqldump.exe';

            $command = "\"{$mysqldumpPath}\" --host={$dbHost} --user={$dbUser} --password={$dbPass} {$dbName} > \"{$filePath}\"";

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($filePath) && filesize($filePath) > 0) {
                Log::info("✅ SQL Dump created: {$filePath}");
                return response()->download($filePath, $filename, [
                    'Content-Type' => 'application/sql',
                ])->deleteFileAfterSend(true);
            }

            Log::error("❌ SQL Dump Failed. Code: {$returnCode}");
            return response()->json(['error' => 'فشل في تصدير قاعدة البيانات'], 500);

        } catch (\Exception $e) {
            Log::error("❌ ExportSqlDump Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // 🟢 Helper لتحويل Array لـ CSV (لو حبيتي تستخدميه لاحقًا)
    private function arrayToCsv($array)
    {
        if (empty($array)) return '';

        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys((array)$array[0]));
        foreach ($array as $row) {
            fputcsv($output, array_values((array)$row));
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
