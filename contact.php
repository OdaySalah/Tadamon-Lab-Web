<?php
/**
 * Enhanced Contact Form Handler
 * مختبرات التضامن الدولية - Enhanced Version
 * Author: Enhanced by AI Assistant
 * Version: 2.0
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS headers for AJAX requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuration
$config = [
    'to_email' => 'info@tadamun-labs.com',
    'from_email' => 'noreply@tadamun-labs.com',
    'subject_prefix' => '[مختبرات التضامن] ',
    'max_message_length' => 5000,
    'required_fields' => ['name', 'email', 'subject', 'message'],
    'honeypot_field' => 'website', // Hidden field to catch bots
    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 5,
        'time_window' => 3600 // 1 hour
    ]
];

// Response class
class ContactResponse {
    public static function success($message = 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً.') {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => $message
        ]);
        exit();
    }
    
    public static function error($message = 'حدث خطأ أثناء إرسال الرسالة. يرجى المحاولة مرة أخرى.', $code = 400) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
        exit();
    }
}

// Security class
class Security {
    public static function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function checkRateLimit($ip, $config) {
        if (!$config['rate_limit']['enabled']) {
            return true;
        }
        
        $log_file = sys_get_temp_dir() . '/contact_rate_limit.log';
        $current_time = time();
        $time_window = $config['rate_limit']['time_window'];
        $max_attempts = $config['rate_limit']['max_attempts'];
        
        // Read existing attempts
        $attempts = [];
        if (file_exists($log_file)) {
            $content = file_get_contents($log_file);
            $attempts = $content ? json_decode($content, true) : [];
        }
        
        // Clean old attempts
        $attempts = array_filter($attempts, function($attempt) use ($current_time, $time_window) {
            return ($current_time - $attempt['time']) < $time_window;
        });
        
        // Count attempts from this IP
        $ip_attempts = array_filter($attempts, function($attempt) use ($ip) {
            return $attempt['ip'] === $ip;
        });
        
        if (count($ip_attempts) >= $max_attempts) {
            return false;
        }
        
        // Log this attempt
        $attempts[] = [
            'ip' => $ip,
            'time' => $current_time
        ];
        
        file_put_contents($log_file, json_encode($attempts));
        return true;
    }
    
    public static function checkHoneypot($honeypot_value) {
        return empty($honeypot_value);
    }
}

// Validation class
class Validator {
    public static function validateRequired($data, $required_fields) {
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return "الحقل '{$field}' مطلوب.";
            }
        }
        return true;
    }
    
    public static function validateLength($text, $max_length) {
        return strlen($text) <= $max_length;
    }
    
    public static function validateName($name) {
        if (strlen($name) < 2) {
            return 'الاسم يجب أن يكون أكثر من حرفين.';
        }
        if (strlen($name) > 100) {
            return 'الاسم طويل جداً.';
        }
        return true;
    }
    
    public static function validateSubject($subject) {
        if (strlen($subject) < 5) {
            return 'الموضوع يجب أن يكون أكثر من 5 أحرف.';
        }
        if (strlen($subject) > 200) {
            return 'الموضوع طويل جداً.';
        }
        return true;
    }
}

// Email class
class EmailSender {
    public static function send($to, $subject, $message, $from_email, $from_name) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 3',
            'Date: ' . date('r')
        ];
        
        $email_body = self::buildEmailTemplate($message, $from_name, $from_email);
        
        return mail($to, $subject, $email_body, implode("\r\n", $headers));
    }
    
    private static function buildEmailTemplate($message, $from_name, $from_email) {
        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>رسالة جديدة من موقع مختبرات التضامن الدولية</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; direction: rtl; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2c5aa0 0%, #4a7bc8 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .message-box { background: white; padding: 20px; border-radius: 8px; border-right: 4px solid #2c5aa0; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
                .info-item { margin: 10px 0; }
                .info-label { font-weight: bold; color: #2c5aa0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>مختبرات التضامن الدولية</h1>
                    <p>رسالة جديدة من موقع المختبر</p>
                </div>
                <div class='content'>
                    <h2>تفاصيل الرسالة:</h2>
                    <div class='info-item'>
                        <span class='info-label'>الاسم:</span> {$from_name}
                    </div>
                    <div class='info-item'>
                        <span class='info-label'>البريد الإلكتروني:</span> {$from_email}
                    </div>
                    <div class='info-item'>
                        <span class='info-label'>التاريخ:</span> " . date('Y-m-d H:i:s') . "
                    </div>
                    <div class='message-box'>
                        <h3>نص الرسالة:</h3>
                        <p>" . nl2br(htmlspecialchars($message)) . "</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>هذه الرسالة تم إرسالها من موقع مختبرات التضامن الدولية</p>
                    <p>للرد على هذه الرسالة، يرجى استخدام البريد الإلكتروني: {$from_email}</p>
                </div>
            </div>
        </body>
        </html>";
    }
}

// Main processing
try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ContactResponse::error('طريقة الطلب غير صحيحة.', 405);
    }
    
    // Get client IP
    $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Check rate limiting
    if (!Security::checkRateLimit($client_ip, $config)) {
        ContactResponse::error('تم تجاوز الحد المسموح من المحاولات. يرجى المحاولة لاحقاً.', 429);
    }
    
    // Get and sanitize input data
    $name = Security::sanitizeInput($_POST['name'] ?? '');
    $email = Security::sanitizeInput($_POST['email'] ?? '');
    $subject = Security::sanitizeInput($_POST['subject'] ?? '');
    $message = Security::sanitizeInput($_POST['message'] ?? '');
    $honeypot = $_POST[$config['honeypot_field']] ?? '';
    
    // Check honeypot (anti-spam)
    if (!Security::checkHoneypot($honeypot)) {
        ContactResponse::error('تم اكتشاف محاولة إرسال غير مشروعة.', 403);
    }
    
    // Validate required fields
    $validation = Validator::validateRequired($_POST, $config['required_fields']);
    if ($validation !== true) {
        ContactResponse::error($validation);
    }
    
    // Validate email
    if (!Security::validateEmail($email)) {
        ContactResponse::error('البريد الإلكتروني غير صحيح.');
    }
    
    // Validate name
    $name_validation = Validator::validateName($name);
    if ($name_validation !== true) {
        ContactResponse::error($name_validation);
    }
    
    // Validate subject
    $subject_validation = Validator::validateSubject($subject);
    if ($subject_validation !== true) {
        ContactResponse::error($subject_validation);
    }
    
    // Validate message length
    if (!Validator::validateLength($message, $config['max_message_length'])) {
        ContactResponse::error('الرسالة طويلة جداً. الحد الأقصى ' . $config['max_message_length'] . ' حرف.');
    }
    
    // Prepare email
    $email_subject = $config['subject_prefix'] . $subject;
    
    // Send email
    $email_sent = EmailSender::send(
        $config['to_email'],
        $email_subject,
        $message,
        $email,
        $name
    );
    
    if ($email_sent) {
        // Log successful submission
        error_log("Contact form submitted successfully from: {$email} ({$client_ip})");
        ContactResponse::success();
    } else {
        // Log email failure
        error_log("Failed to send contact form email from: {$email} ({$client_ip})");
        ContactResponse::error('فشل في إرسال البريد الإلكتروني. يرجى المحاولة لاحقاً.', 500);
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Contact form error: " . $e->getMessage());
    ContactResponse::error('حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.', 500);
}
?>

