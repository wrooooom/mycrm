#!/usr/bin/env php
<?php
/**
 * Production Cleanup Script
 * Removes debug code, test files, and prepares for deployment
 */

class ProductionCleanup {
    private $projectRoot;
    private $dryRun = false;
    private $report = [];
    
    public function __construct($projectRoot, $dryRun = false) {
        $this->projectRoot = $projectRoot;
        $this->dryRun = $dryRun;
    }
    
    public function run() {
        echo "🚀 Starting Production Cleanup...\n\n";
        
        if ($this->dryRun) {
            echo "⚠️  DRY RUN MODE - No files will be modified\n\n";
        }
        
        $this->removeDebugCode();
        $this->removeConsoleLog();
        $this->removeTestFiles();
        $this->removeBackupFiles();
        $this->checkTodoComments();
        $this->validateRequiredFiles();
        
        $this->printReport();
    }
    
    /**
     * Remove debug code from PHP files
     */
    private function removeDebugCode() {
        echo "📝 Removing debug code from PHP files...\n";
        
        $phpFiles = $this->getFiles('*.php');
        $debugPatterns = [
            '/var_dump\([^;]+\);?/i',
            '/print_r\([^;]+\);?/i',
            '/var_export\([^;]+\);?/i',
            '/debug\([^;]+\);?/i',
            '/\s*echo\s+["\']DEBUG:/i',
        ];
        
        $count = 0;
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $originalContent = $content;
            
            foreach ($debugPatterns as $pattern) {
                $content = preg_replace($pattern, '', $content);
            }
            
            if ($content !== $originalContent) {
                $count++;
                if (!$this->dryRun) {
                    file_put_contents($file, $content);
                }
                $this->report['debug_removed'][] = $file;
            }
        }
        
        echo "  ✓ Removed debug code from {$count} files\n\n";
    }
    
    /**
     * Remove console.log from JavaScript files
     */
    private function removeConsoleLog() {
        echo "📝 Removing console.log from JavaScript files...\n";
        
        $jsFiles = $this->getFiles('*.js', ['js']);
        $patterns = [
            '/console\.log\([^;]+\);?/i',
            '/console\.debug\([^;]+\);?/i',
            '/console\.info\([^;]+\);?/i',
            '/console\.warn\([^;]+\);?/i',
        ];
        
        $count = 0;
        foreach ($jsFiles as $file) {
            // Skip minified files
            if (strpos($file, '.min.js') !== false) continue;
            
            $content = file_get_contents($file);
            $originalContent = $content;
            
            foreach ($patterns as $pattern) {
                $content = preg_replace($pattern, '', $content);
            }
            
            if ($content !== $originalContent) {
                $count++;
                if (!$this->dryRun) {
                    file_put_contents($file, $content);
                }
                $this->report['console_removed'][] = $file;
            }
        }
        
        echo "  ✓ Removed console.log from {$count} files\n\n";
    }
    
    /**
     * Remove test files
     */
    private function removeTestFiles() {
        echo "📝 Removing test files...\n";
        
        $testPatterns = [
            'test_*.php',
            '*_test.php',
            'test-*.php',
            '*-test.php',
            'debug*.php',
            '*_backup.php',
            '*.bak',
        ];
        
        $count = 0;
        foreach ($testPatterns as $pattern) {
            $files = $this->getFiles($pattern);
            foreach ($files as $file) {
                $count++;
                if (!$this->dryRun) {
                    unlink($file);
                }
                $this->report['test_files_removed'][] = $file;
            }
        }
        
        echo "  ✓ Removed {$count} test files\n\n";
    }
    
    /**
     * Remove backup files
     */
    private function removeBackupFiles() {
        echo "📝 Removing backup files...\n";
        
        $backupPatterns = [
            '*.backup',
            '*.old',
            '*~',
            '*.swp',
            '.DS_Store',
        ];
        
        $count = 0;
        foreach ($backupPatterns as $pattern) {
            $files = $this->getFiles($pattern);
            foreach ($files as $file) {
                $count++;
                if (!$this->dryRun) {
                    unlink($file);
                }
                $this->report['backup_files_removed'][] = $file;
            }
        }
        
        echo "  ✓ Removed {$count} backup files\n\n";
    }
    
    /**
     * Check for TODO comments
     */
    private function checkTodoComments() {
        echo "📝 Checking for TODO/FIXME comments...\n";
        
        $files = array_merge(
            $this->getFiles('*.php'),
            $this->getFiles('*.js', ['js'])
        );
        
        $todoPatterns = [
            '/\/\/\s*(TODO|FIXME|HACK|XXX):/i',
            '/#\s*(TODO|FIXME|HACK|XXX):/i',
            '/\/\*\s*(TODO|FIXME|HACK|XXX):/i',
        ];
        
        $count = 0;
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNum => $line) {
                foreach ($todoPatterns as $pattern) {
                    if (preg_match($pattern, $line, $matches)) {
                        $count++;
                        $this->report['todo_found'][] = [
                            'file' => $file,
                            'line' => $lineNum + 1,
                            'text' => trim($line)
                        ];
                    }
                }
            }
        }
        
        echo "  ⚠️  Found {$count} TODO/FIXME comments\n\n";
    }
    
    /**
     * Validate required files exist
     */
    private function validateRequiredFiles() {
        echo "📝 Validating required files...\n";
        
        $requiredFiles = [
            '.env.example',
            '.gitignore',
            'README.md',
            'INSTALLATION.md',
            'DOCUMENTATION.md',
            'API.md',
            'composer.json',
            'Dockerfile',
            'docker-compose.yml',
        ];
        
        $missing = [];
        foreach ($requiredFiles as $file) {
            $path = $this->projectRoot . '/' . $file;
            if (!file_exists($path)) {
                $missing[] = $file;
                $this->report['missing_files'][] = $file;
            }
        }
        
        if (empty($missing)) {
            echo "  ✓ All required files present\n\n";
        } else {
            echo "  ⚠️  Missing files: " . implode(', ', $missing) . "\n\n";
        }
    }
    
    /**
     * Get files matching pattern
     */
    private function getFiles($pattern, $dirs = null) {
        if ($dirs === null) {
            $dirs = ['', 'includes', 'api', 'config', 'templates', 'views'];
        }
        
        $files = [];
        foreach ($dirs as $dir) {
            $path = $this->projectRoot . ($dir ? '/' . $dir : '');
            if (is_dir($path)) {
                $found = glob($path . '/' . $pattern);
                if ($found) {
                    $files = array_merge($files, $found);
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Print cleanup report
     */
    private function printReport() {
        echo "\n";
        echo str_repeat('=', 60) . "\n";
        echo "CLEANUP REPORT\n";
        echo str_repeat('=', 60) . "\n\n";
        
        if (!empty($this->report['debug_removed'])) {
            echo "Debug Code Removed:\n";
            foreach ($this->report['debug_removed'] as $file) {
                echo "  - " . basename($file) . "\n";
            }
            echo "\n";
        }
        
        if (!empty($this->report['console_removed'])) {
            echo "Console.log Removed:\n";
            foreach ($this->report['console_removed'] as $file) {
                echo "  - " . basename($file) . "\n";
            }
            echo "\n";
        }
        
        if (!empty($this->report['test_files_removed'])) {
            echo "Test Files Removed:\n";
            foreach ($this->report['test_files_removed'] as $file) {
                echo "  - " . basename($file) . "\n";
            }
            echo "\n";
        }
        
        if (!empty($this->report['todo_found'])) {
            echo "⚠️  TODO/FIXME Comments Found:\n";
            foreach ($this->report['todo_found'] as $todo) {
                echo "  - " . basename($todo['file']) . ":" . $todo['line'] . " - " . $todo['text'] . "\n";
            }
            echo "\n";
        }
        
        if (!empty($this->report['missing_files'])) {
            echo "⚠️  Missing Required Files:\n";
            foreach ($this->report['missing_files'] as $file) {
                echo "  - " . $file . "\n";
            }
            echo "\n";
        }
        
        if ($this->dryRun) {
            echo "⚠️  DRY RUN - No changes were made\n";
            echo "Run without --dry-run to apply changes\n\n";
        } else {
            echo "✅ Cleanup completed successfully!\n\n";
        }
    }
}

// CLI Handler
if (php_sapi_name() === 'cli') {
    $dryRun = in_array('--dry-run', $argv) || in_array('-d', $argv);
    $projectRoot = dirname(__DIR__);
    
    $cleanup = new ProductionCleanup($projectRoot, $dryRun);
    $cleanup->run();
} else {
    die('This script must be run from command line');
}
