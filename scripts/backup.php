#!/usr/bin/env php
<?php
/**
 * Database Backup Script
 * Creates automatic backups with compression and rotation
 */

// Include configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/logger.php';

class DatabaseBackup {
    private $host;
    private $port;
    private $database;
    private $username;
    private $password;
    private $backupDir;
    private $retentionDays;
    private $logger;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->port = DB_PORT ?? 3306;
        $this->database = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->backupDir = __DIR__ . '/../backups';
        $this->retentionDays = 30;
        $this->logger = Logger::getInstance();
        
        // Create backup directory
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Create full database backup
     */
    public function createBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup_{$this->database}_{$timestamp}.sql";
            $filepath = $this->backupDir . '/' . $filename;
            
            $this->logger->info('Starting database backup', ['database' => $this->database]);
            
            // Execute mysqldump
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
                escapeshellarg($this->host),
                escapeshellarg($this->port),
                escapeshellarg($this->username),
                escapeshellarg($this->password),
                escapeshellarg($this->database),
                escapeshellarg($filepath)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception('mysqldump failed with code ' . $returnCode);
            }
            
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                throw new Exception('Backup file is empty or does not exist');
            }
            
            // Compress backup
            $this->compressBackup($filepath);
            
            // Clean old backups
            $this->cleanOldBackups();
            
            $this->logger->info('Database backup completed successfully', [
                'file' => $filename . '.gz',
                'size' => $this->formatBytes(filesize($filepath . '.gz'))
            ]);
            
            echo "✓ Backup created successfully: {$filename}.gz\n";
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Backup failed', [
                'error' => $e->getMessage()
            ]);
            
            echo "✗ Backup failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Compress backup file
     */
    private function compressBackup($filepath) {
        if (!function_exists('gzopen')) {
            $this->logger->warning('Gzip not available, skipping compression');
            return;
        }
        
        $gzFile = $filepath . '.gz';
        
        $fp = fopen($filepath, 'rb');
        $gz = gzopen($gzFile, 'wb9');
        
        while (!feof($fp)) {
            gzwrite($gz, fread($fp, 8192));
        }
        
        fclose($fp);
        gzclose($gz);
        
        // Remove uncompressed file
        unlink($filepath);
    }
    
    /**
     * Clean old backups based on retention policy
     */
    private function cleanOldBackups() {
        $files = glob($this->backupDir . '/backup_*.sql.gz');
        $cutoffTime = time() - ($this->retentionDays * 86400);
        $removedCount = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $removedCount++;
            }
        }
        
        if ($removedCount > 0) {
            $this->logger->info("Cleaned up old backups", ['count' => $removedCount]);
            echo "✓ Removed {$removedCount} old backup(s)\n";
        }
    }
    
    /**
     * List all available backups
     */
    public function listBackups() {
        $files = glob($this->backupDir . '/backup_*.sql.gz');
        
        if (empty($files)) {
            echo "No backups found.\n";
            return;
        }
        
        echo "\nAvailable Backups:\n";
        echo str_repeat('-', 80) . "\n";
        
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        foreach ($files as $file) {
            $filename = basename($file);
            $size = $this->formatBytes(filesize($file));
            $date = date('Y-m-d H:i:s', filemtime($file));
            
            printf("%-50s %10s %s\n", $filename, $size, $date);
        }
        
        echo str_repeat('-', 80) . "\n";
    }
    
    /**
     * Restore database from backup
     */
    public function restoreBackup($backupFile) {
        try {
            $filepath = $this->backupDir . '/' . $backupFile;
            
            if (!file_exists($filepath)) {
                throw new Exception('Backup file not found: ' . $backupFile);
            }
            
            echo "⚠ WARNING: This will replace all current database data!\n";
            echo "Are you sure you want to restore from backup? (yes/no): ";
            
            $handle = fopen("php://stdin", "r");
            $line = trim(fgets($handle));
            
            if ($line !== 'yes') {
                echo "Restore cancelled.\n";
                return false;
            }
            
            $this->logger->warning('Starting database restore', ['backup' => $backupFile]);
            
            // Decompress if needed
            $sqlFile = $filepath;
            if (substr($filepath, -3) === '.gz') {
                echo "Decompressing backup...\n";
                $sqlFile = substr($filepath, 0, -3);
                
                $gz = gzopen($filepath, 'rb');
                $fp = fopen($sqlFile, 'wb');
                
                while (!gzeof($gz)) {
                    fwrite($fp, gzread($gz, 8192));
                }
                
                fclose($fp);
                gzclose($gz);
            }
            
            // Restore database
            echo "Restoring database...\n";
            
            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
                escapeshellarg($this->host),
                escapeshellarg($this->port),
                escapeshellarg($this->username),
                escapeshellarg($this->password),
                escapeshellarg($this->database),
                escapeshellarg($sqlFile)
            );
            
            exec($command, $output, $returnCode);
            
            // Clean up decompressed file
            if ($sqlFile !== $filepath) {
                unlink($sqlFile);
            }
            
            if ($returnCode !== 0) {
                throw new Exception('Restore failed with code ' . $returnCode);
            }
            
            $this->logger->info('Database restore completed', ['backup' => $backupFile]);
            echo "✓ Database restored successfully from: {$backupFile}\n";
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Restore failed', [
                'error' => $e->getMessage(),
                'backup' => $backupFile
            ]);
            
            echo "✗ Restore failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// CLI Handler
if (php_sapi_name() === 'cli') {
    $backup = new DatabaseBackup();
    
    $command = $argv[1] ?? 'create';
    
    switch ($command) {
        case 'create':
            $backup->createBackup();
            break;
            
        case 'list':
            $backup->listBackups();
            break;
            
        case 'restore':
            if (!isset($argv[2])) {
                echo "Usage: php backup.php restore <backup_filename>\n";
                exit(1);
            }
            $backup->restoreBackup($argv[2]);
            break;
            
        default:
            echo "Usage: php backup.php [create|list|restore]\n";
            echo "  create  - Create new backup\n";
            echo "  list    - List all backups\n";
            echo "  restore - Restore from backup\n";
            exit(1);
    }
}
