<?php
/**
 * Security Module - Production Ready
 * CSRF Protection, Rate Limiting, Input Validation, XSS Prevention
 */

class SecurityManager {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function setPDO($pdo) {
        $this->pdo = $pdo;
    }
    
    // ========================================
    // CSRF Protection
    // ========================================
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token HTML input
     */
    public function getCSRFInput() {
        $token = $this->generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Get CSRF token meta tag
     */
    public function getCSRFMeta() {
        $token = $this->generateCSRFToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Verify CSRF for request
     */
    public function verifyCSRF() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!$token || !$this->validateCSRFToken($token)) {
            http_response_code(403);
            if ($this->isAjaxRequest()) {
                echo json_encode(['error' => 'CSRF token validation failed']);
            } else {
                die('CSRF token validation failed');
            }
            exit;
        }
        return true;
    }
    
    // ========================================
    // Rate Limiting
    // ========================================
    
    /**
     * Check rate limit
     */
    public function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 60) {
        $identifier = $this->getClientIdentifier();
        $key = "ratelimit_{$action}_{$identifier}";
        
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        $now = time();
        
        // Clean old entries
        if (isset($_SESSION['rate_limits'][$key])) {
            $_SESSION['rate_limits'][$key] = array_filter(
                $_SESSION['rate_limits'][$key],
                function($timestamp) use ($now, $timeWindow) {
                    return ($now - $timestamp) < $timeWindow;
                }
            );
        } else {
            $_SESSION['rate_limits'][$key] = [];
        }
        
        // Check if limit exceeded
        if (count($_SESSION['rate_limits'][$key]) >= $maxAttempts) {
            $oldestAttempt = min($_SESSION['rate_limits'][$key]);
            $retryAfter = $timeWindow - ($now - $oldestAttempt);
            
            http_response_code(429);
            header("Retry-After: $retryAfter");
            
            if ($this->isAjaxRequest()) {
                echo json_encode([
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $retryAfter
                ]);
            } else {
                die('Too many requests. Please try again later.');
            }
            exit;
        }
        
        // Add current attempt
        $_SESSION['rate_limits'][$key][] = $now;
        
        return true;
    }
    
    /**
     * Get client identifier (IP + User Agent hash)
     */
    private function getClientIdentifier() {
        $ip = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return md5($ip . $userAgent);
    }
    
    /**
     * Get client IP address
     */
    public function getClientIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    // ========================================
    // Input Validation & Sanitization
    // ========================================
    
    /**
     * Sanitize string input
     */
    public function sanitizeString($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize email
     */
    public function sanitizeEmail($email) {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Validate email
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize integer
     */
    public function sanitizeInt($input) {
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Validate integer
     */
    public function validateInt($input, $min = null, $max = null) {
        $options = [];
        if ($min !== null) $options['min_range'] = $min;
        if ($max !== null) $options['max_range'] = $max;
        
        return filter_var($input, FILTER_VALIDATE_INT, ['options' => $options]) !== false;
    }
    
    /**
     * Sanitize phone number
     */
    public function sanitizePhone($phone) {
        return preg_replace('/[^0-9+\-() ]/', '', $phone);
    }
    
    /**
     * Validate URL
     */
    public function validateURL($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Prevent XSS
     */
    public function preventXSS($input) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize filename
     */
    public function sanitizeFilename($filename) {
        // Remove any path components
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        return $filename;
    }
    
    // ========================================
    // Password Security
    // ========================================
    
    /**
     * Hash password using bcrypt
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Validate password strength
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    // ========================================
    // Session Security
    // ========================================
    
    /**
     * Configure secure session
     */
    public function configureSecureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
    }
    
    /**
     * Regenerate session ID
     */
    public function regenerateSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    // ========================================
    // Security Headers
    // ========================================
    
    /**
     * Set security headers
     */
    public function setSecurityHeaders() {
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://api-maps.yandex.ru; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' data: https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; connect-src 'self'");
        
        // X-Frame-Options
        header('X-Frame-Options: SAMEORIGIN');
        
        // X-Content-Type-Options
        header('X-Content-Type-Options: nosniff');
        
        // X-XSS-Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer-Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions-Policy
        header('Permissions-Policy: geolocation=(self), microphone=(), camera=()');
        
        // HSTS (if HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    /**
     * Redirect to HTTPS
     */
    public function forceHTTPS() {
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect, true, 301);
            exit;
        }
    }
    
    // ========================================
    // Utility Methods
    // ========================================
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'details' => $details
        ];
        
        error_log('SECURITY: ' . json_encode($logEntry));
        
        // Store in database if PDO available
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO security_logs (event, ip_address, user_agent, user_id, details, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())"
                );
                $stmt->execute([
                    $event,
                    $logEntry['ip'],
                    $logEntry['user_agent'],
                    $logEntry['user_id'],
                    json_encode($details)
                ]);
            } catch (Exception $e) {
                error_log('Failed to log security event: ' . $e->getMessage());
            }
        }
    }
}

// Global helper functions
function csrf_token() {
    return SecurityManager::getInstance()->generateCSRFToken();
}

function csrf_input() {
    return SecurityManager::getInstance()->getCSRFInput();
}

function csrf_meta() {
    return SecurityManager::getInstance()->getCSRFMeta();
}

function verify_csrf() {
    return SecurityManager::getInstance()->verifyCSRF();
}

function sanitize($input) {
    return SecurityManager::getInstance()->sanitizeString($input);
}

function check_rate_limit($action, $maxAttempts = 5, $timeWindow = 60) {
    return SecurityManager::getInstance()->checkRateLimit($action, $maxAttempts, $timeWindow);
}

// Initialize security
$security = SecurityManager::getInstance();
$security->configureSecureSession();

// Set security headers for all pages
if (!headers_sent()) {
    $security->setSecurityHeaders();
}
