<?php
/**
 * Email Provider Integration
 * SMTP email sending with template support
 */

class EmailProvider {
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    private $fromAddress;
    private $fromName;
    private $logger;
    private $db;
    private $testMode;
    
    public function __construct($config = []) {
        $this->host = $config['host'] ?? getenv('MAIL_HOST') ?? 'localhost';
        $this->port = $config['port'] ?? getenv('MAIL_PORT') ?? 587;
        $this->username = $config['username'] ?? getenv('MAIL_USERNAME');
        $this->password = $config['password'] ?? getenv('MAIL_PASSWORD');
        $this->encryption = $config['encryption'] ?? getenv('MAIL_ENCRYPTION') ?? 'tls';
        $this->fromAddress = $config['from_address'] ?? getenv('MAIL_FROM_ADDRESS') ?? 'noreply@proftransfer.com';
        $this->fromName = $config['from_name'] ?? getenv('MAIL_FROM_NAME') ?? 'CRM.PROFTRANSFER';
        $this->testMode = $config['test_mode'] ?? (getenv('MAIL_TEST_MODE') === 'true');
        
        if (class_exists('Logger')) {
            $this->logger = Logger::getInstance();
        }
        
        if (class_exists('Database')) {
            $database = new Database();
            $this->db = $database->getConnection();
        }
    }
    
    public function send($to, $subject, $body, $options = []) {
        $cc = $options['cc'] ?? null;
        $bcc = $options['bcc'] ?? null;
        $replyTo = $options['reply_to'] ?? null;
        $attachments = $options['attachments'] ?? [];
        $isHtml = $options['is_html'] ?? true;
        
        if ($this->testMode) {
            $this->log(
                $options['user_id'] ?? null,
                $options['application_id'] ?? null,
                $to,
                $cc,
                $subject,
                $body,
                $options['template'] ?? null,
                'sent'
            );
            
            if ($this->logger) {
                $this->logger->info("Email sent (test mode)", [
                    'to' => $to,
                    'subject' => $subject
                ]);
            }
            
            return ['success' => true, 'message_id' => 'test_' . uniqid()];
        }
        
        try {
            $headers = $this->buildHeaders($cc, $bcc, $replyTo, $isHtml);
            $message = $this->buildMessage($body, $attachments, $isHtml);
            
            $result = mail($to, $subject, $message, $headers);
            
            if ($result) {
                $this->log(
                    $options['user_id'] ?? null,
                    $options['application_id'] ?? null,
                    $to,
                    $cc,
                    $subject,
                    $body,
                    $options['template'] ?? null,
                    'sent'
                );
                
                if ($this->logger) {
                    $this->logger->info("Email sent successfully", [
                        'to' => $to,
                        'subject' => $subject
                    ]);
                }
                
                return ['success' => true];
            } else {
                $this->log(
                    $options['user_id'] ?? null,
                    $options['application_id'] ?? null,
                    $to,
                    $cc,
                    $subject,
                    $body,
                    $options['template'] ?? null,
                    'failed',
                    'Failed to send email'
                );
                
                return ['success' => false, 'error' => 'Failed to send email'];
            }
        } catch (Exception $e) {
            $this->log(
                $options['user_id'] ?? null,
                $options['application_id'] ?? null,
                $to,
                $cc,
                $subject,
                $body,
                $options['template'] ?? null,
                'failed',
                $e->getMessage()
            );
            
            if ($this->logger) {
                $this->logger->error("Email send failed", [
                    'to' => $to,
                    'error' => $e->getMessage()
                ]);
            }
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function sendTemplate($to, $template, $data, $options = []) {
        $templatePath = __DIR__ . '/../../templates/emails/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            if ($this->logger) {
                $this->logger->error("Email template not found", ['template' => $template]);
            }
            return ['success' => false, 'error' => 'Template not found'];
        }
        
        ob_start();
        extract($data);
        include $templatePath;
        $body = ob_get_clean();
        
        $options['template'] = $template;
        $options['is_html'] = true;
        
        $subject = $data['subject'] ?? 'Уведомление от CRM.PROFTRANSFER';
        
        return $this->send($to, $subject, $body, $options);
    }
    
    private function buildHeaders($cc, $bcc, $replyTo, $isHtml) {
        $headers = [];
        $headers[] = "From: {$this->fromName} <{$this->fromAddress}>";
        
        if ($cc) {
            $headers[] = "Cc: {$cc}";
        }
        
        if ($bcc) {
            $headers[] = "Bcc: {$bcc}";
        }
        
        if ($replyTo) {
            $headers[] = "Reply-To: {$replyTo}";
        }
        
        if ($isHtml) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        $headers[] = "X-Mailer: CRM.PROFTRANSFER";
        
        return implode("\r\n", $headers);
    }
    
    private function buildMessage($body, $attachments, $isHtml) {
        if (empty($attachments)) {
            return $body;
        }
        
        $boundary = md5(time());
        $message = "--{$boundary}\r\n";
        
        if ($isHtml) {
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        } else {
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        }
        
        $message .= $body . "\r\n";
        
        foreach ($attachments as $file) {
            if (file_exists($file)) {
                $filename = basename($file);
                $content = chunk_split(base64_encode(file_get_contents($file)));
                
                $message .= "--{$boundary}\r\n";
                $message .= "Content-Type: application/octet-stream; name=\"{$filename}\"\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n";
                $message .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
                $message .= $content . "\r\n";
            }
        }
        
        $message .= "--{$boundary}--";
        
        return $message;
    }
    
    private function log($userId, $applicationId, $to, $cc, $subject, $body, $template, $status, $error = null) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO email_log (user_id, application_id, to_email, cc_email, subject, body, template, status, error_message, sent_at) 
                 VALUES (:user_id, :application_id, :to_email, :cc_email, :subject, :body, :template, :status, :error, :sent_at)"
            );
            
            $stmt->execute([
                ':user_id' => $userId,
                ':application_id' => $applicationId,
                ':to_email' => $to,
                ':cc_email' => $cc,
                ':subject' => $subject,
                ':body' => substr($body, 0, 5000),
                ':template' => $template,
                ':status' => $status,
                ':error' => $error,
                ':sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null
            ]);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Failed to log email", ['error' => $e->getMessage()]);
            }
        }
    }
    
    public function sendUserRegistration($user) {
        return $this->sendTemplate($user['email'], 'user_registration', [
            'subject' => 'Добро пожаловать в CRM.PROFTRANSFER',
            'user' => $user
        ], ['user_id' => $user['id'] ?? null]);
    }
    
    public function sendPasswordReset($user, $resetToken) {
        $resetLink = getenv('APP_URL') . "/reset-password.php?token={$resetToken}";
        
        return $this->sendTemplate($user['email'], 'password_reset', [
            'subject' => 'Восстановление пароля',
            'user' => $user,
            'resetLink' => $resetLink
        ], ['user_id' => $user['id'] ?? null]);
    }
    
    public function sendApplicationAssigned($driver, $application) {
        return $this->sendTemplate($driver['email'], 'application_assigned', [
            'subject' => 'Новая заявка назначена',
            'driver' => $driver,
            'application' => $application
        ], [
            'user_id' => $driver['user_id'] ?? null,
            'application_id' => $application['id'] ?? null
        ]);
    }
    
    public function sendApplicationStatusChanged($customer, $application, $oldStatus, $newStatus) {
        return $this->sendTemplate($customer['email'], 'application_status_changed', [
            'subject' => 'Статус заявки изменен',
            'customer' => $customer,
            'application' => $application,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ], [
            'application_id' => $application['id'] ?? null
        ]);
    }
    
    public function sendPaymentNotification($customer, $payment, $application) {
        return $this->sendTemplate($customer['email'], 'payment_notification', [
            'subject' => 'Уведомление о платеже',
            'customer' => $customer,
            'payment' => $payment,
            'application' => $application
        ], [
            'application_id' => $application['id'] ?? null
        ]);
    }
    
    public function sendWeeklyReport($manager, $reportData) {
        return $this->sendTemplate($manager['email'], 'weekly_report', [
            'subject' => 'Еженедельный отчет',
            'manager' => $manager,
            'report' => $reportData
        ], [
            'user_id' => $manager['id'] ?? null
        ]);
    }
}
