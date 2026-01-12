<?php
/**
 * Centralized Logging System
 * Structured JSON logging with rotation
 */

class Logger {
    private static $instance = null;
    private $logDir;
    private $logLevel;
    private $maxFileSize = 10485760; // 10MB
    private $maxFiles = 10;
    
    const EMERGENCY = 'EMERGENCY';
    const ALERT = 'ALERT';
    const CRITICAL = 'CRITICAL';
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const NOTICE = 'NOTICE';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    
    private $levels = [
        self::EMERGENCY => 0,
        self::ALERT => 1,
        self::CRITICAL => 2,
        self::ERROR => 3,
        self::WARNING => 4,
        self::NOTICE => 5,
        self::INFO => 6,
        self::DEBUG => 7
    ];
    
    private function __construct() {
        $this->logDir = __DIR__ . '/../logs';
        $this->logLevel = defined('LOG_LEVEL') ? constant('LOG_LEVEL') : self::INFO;
        
        // Create logs directory if not exists
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Set error handler
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        register_shutdown_function([$this, 'shutdownHandler']);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log message
     */
    private function log($level, $message, array $context = []) {
        // Check if level should be logged
        if ($this->levels[$level] > $this->levels[$this->logLevel]) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'cli',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB',
            'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB'
        ];
        
        // Add stack trace for errors
        if ($this->levels[$level] <= $this->levels[self::ERROR]) {
            $logEntry['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        }
        
        $this->writeLog($level, $logEntry);
    }
    
    /**
     * Write log to file
     */
    private function writeLog($level, array $logEntry) {
        $logFile = $this->getLogFile($level);
        
        // Check if log rotation needed
        if (file_exists($logFile) && filesize($logFile) > $this->maxFileSize) {
            $this->rotateLog($logFile);
        }
        
        // Format: JSON per line
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        
        // Write to file
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Also write critical errors to separate file
        if ($this->levels[$level] <= $this->levels[self::CRITICAL]) {
            $criticalFile = $this->logDir . '/critical.log';
            file_put_contents($criticalFile, $logLine, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Get log file path
     */
    private function getLogFile($level) {
        $date = date('Y-m-d');
        
        // Separate files for different levels
        if ($this->levels[$level] <= $this->levels[self::ERROR]) {
            return $this->logDir . "/error-{$date}.log";
        } else {
            return $this->logDir . "/app-{$date}.log";
        }
    }
    
    /**
     * Rotate log file
     */
    private function rotateLog($logFile) {
        // Rename current log
        $timestamp = date('YmdHis');
        $rotatedFile = $logFile . '.' . $timestamp;
        rename($logFile, $rotatedFile);
        
        // Compress rotated file
        if (function_exists('gzopen')) {
            $this->compressLog($rotatedFile);
        }
        
        // Clean old logs
        $this->cleanOldLogs();
    }
    
    /**
     * Compress log file
     */
    private function compressLog($file) {
        $gzFile = $file . '.gz';
        $fp = fopen($file, 'rb');
        $gz = gzopen($gzFile, 'wb9');
        
        while (!feof($fp)) {
            gzwrite($gz, fread($fp, 8192));
        }
        
        fclose($fp);
        gzclose($gz);
        unlink($file);
    }
    
    /**
     * Clean old log files
     */
    private function cleanOldLogs() {
        $files = glob($this->logDir . '/*.log*');
        
        // Sort by modification time
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Remove old files
        $filesToRemove = array_slice($files, $this->maxFiles);
        foreach ($filesToRemove as $file) {
            unlink($file);
        }
    }
    
    // ========================================
    // Error Handlers
    // ========================================
    
    /**
     * PHP Error Handler
     */
    public function errorHandler($errno, $errstr, $errfile, $errline) {
        $errorTypes = [
            E_ERROR => self::ERROR,
            E_WARNING => self::WARNING,
            E_NOTICE => self::NOTICE,
            E_USER_ERROR => self::ERROR,
            E_USER_WARNING => self::WARNING,
            E_USER_NOTICE => self::NOTICE,
            E_STRICT => self::NOTICE,
            E_DEPRECATED => self::NOTICE
        ];
        
        $level = $errorTypes[$errno] ?? self::ERROR;
        
        $this->log($level, $errstr, [
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ]);
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Exception Handler
     */
    public function exceptionHandler($exception) {
        $this->critical('Uncaught exception', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Show user-friendly error page
        if (!headers_sent()) {
            http_response_code(500);
        }
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo '<pre>' . $exception->getMessage() . "\n" . $exception->getTraceAsString() . '</pre>';
        } else {
            include __DIR__ . '/../views/error.php';
        }
    }
    
    /**
     * Shutdown Handler for fatal errors
     */
    public function shutdownHandler() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->critical('Fatal error', [
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $error['type']
            ]);
        }
    }
    
    // ========================================
    // Public Logging Methods
    // ========================================
    
    public function emergency($message, array $context = []) {
        $this->log(self::EMERGENCY, $message, $context);
    }
    
    public function alert($message, array $context = []) {
        $this->log(self::ALERT, $message, $context);
    }
    
    public function critical($message, array $context = []) {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    public function error($message, array $context = []) {
        $this->log(self::ERROR, $message, $context);
    }
    
    public function warning($message, array $context = []) {
        $this->log(self::WARNING, $message, $context);
    }
    
    public function notice($message, array $context = []) {
        $this->log(self::NOTICE, $message, $context);
    }
    
    public function info($message, array $context = []) {
        $this->log(self::INFO, $message, $context);
    }
    
    public function debug($message, array $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Query log for specific level or time range
     */
    public function query($level = null, $from = null, $to = null, $limit = 100) {
        $logs = [];
        $files = glob($this->logDir . '/*.log');
        
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $entry = json_decode($line, true);
                
                if ($entry === null) continue;
                
                // Filter by level
                if ($level && $entry['level'] !== $level) continue;
                
                // Filter by time range
                if ($from && strtotime($entry['timestamp']) < strtotime($from)) continue;
                if ($to && strtotime($entry['timestamp']) > strtotime($to)) continue;
                
                $logs[] = $entry;
                
                if (count($logs) >= $limit) break 2;
            }
        }
        
        return $logs;
    }
}

// Global helper functions
function logger() {
    return Logger::getInstance();
}

function log_debug($message, $context = []) {
    Logger::getInstance()->debug($message, $context);
}

function log_info($message, $context = []) {
    Logger::getInstance()->info($message, $context);
}

function log_warning($message, $context = []) {
    Logger::getInstance()->warning($message, $context);
}

function log_error($message, $context = []) {
    Logger::getInstance()->error($message, $context);
}

function log_critical($message, $context = []) {
    Logger::getInstance()->critical($message, $context);
}

// Initialize logger
$logger = Logger::getInstance();
