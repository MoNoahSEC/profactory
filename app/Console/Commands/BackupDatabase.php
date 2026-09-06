<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature   = 'db:backup';
    protected $description = 'إنشاء نسخة احتياطية من قاعدة البيانات';

    public function handle(): int
    {
        $dbPath     = database_path('database.sqlite');
        $backupDir  = storage_path('backups');
        $timestamp  = now()->format('Y-m-d_H-i-s');
        $backupFile = "{$backupDir}/backup_{$timestamp}.sqlite";

        if (! File::exists($dbPath)) {
            $this->warn('قاعدة البيانات غير موجودة، تجاوز النسخ الاحتياطي.');
            return 0;
        }

        File::ensureDirectoryExists($backupDir);

        // Use SQLite VACUUM INTO for a clean, locked-safe backup
        try {
            DB::statement("VACUUM INTO '{$backupFile}'");
        } catch (\Exception $e) {
            // Fallback: simple file copy
            File::copy($dbPath, $backupFile);
        }

        $this->info("✅ تم إنشاء النسخة الاحتياطية: backup_{$timestamp}.sqlite");

        // Keep only the last 7 backups
        $backups = collect(File::files($backupDir))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.sqlite'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        $backups->skip(7)->each(function ($file) {
            File::delete($file->getPathname());
            $this->line("  🗑️  حُذفت نسخة قديمة: " . $file->getFilename());
        });

        return 0;
    }
}
