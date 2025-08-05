<?php
/**
 * Enhanced Appointment Booking Handler
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
    'to_email' => 'appointments@tadamun-labs.com',
    'from_email' => 'noreply@tadamun-labs.com',
    'subject_prefix' => '[حجز موعد] ',
    'required_fields' => ['name', 'phone', 'email', 'date', 'time', 'service'],
    'honeypot_field' => 'website',
    'working_hours' => [
        'start' => '07:00',
        'end' => '22:00',
        'closed_days' => [5] // Friday (0 = Sunday, 6 = Saturday)
    ],
    'services' => [
        'pre_marriage' => 'فحوصات ما قبل الزواج',
        'infectious_diseases' => 'فحوصات الأمراض المعدية',
        'genetic_diseases' => 'فحوصات الأمراض الوراثية',
        'blood_tests' => 'تحاليل الدم الشاملة',
        'urine_tests' => 'فحوصات البول',
        'hormones' => 'تحاليل الهرمونات',
        'vitamins' => 'فحوصات الفيتامينات',
        'liver_function' => 'وظائف الكبد',
        'kidney_function' => 'وظائف الكلى',
        'heart_tests' => 'فحوصات القلب',
        'diabetes' => 'فحوصات السكري',
        'lipids' => 'فحوصات الدهون',
        'immunity' => 'فحوصات المناعة',
        'other' => 'أخرى'
    ],
    'branches' => [
        'main' => 'الفرع الرئيسي - تعز',
        'nashma' => 'فرع النشمة',
        'hawban' => 'فرع الحوبان'
    ]
];

// Response class
class BookingResponse {
    public static function success($message = 'تم حجز موعدك بنجاح. سنتواصل معك لتأكيد الموعد.') {
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => $message
        ]);
        exit();
    }
    
    public static function error($message = 'حدث خطأ أثناء حجز الموعد. يرجى المحاولة مرة أخرى.', $code = 400) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
        exit();
    }
}

// Security class
class BookingSecurity {
    public static function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePhone($phone) {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Check if it's a valid Yemeni phone number
        if (preg_match('/^(\+967|967|0)?[1-9][0-9]{7,8}$/', $phone)) {
            return true;
        }
        
        return false;
    }
    
    public static function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    public static function validateTime($time) {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
    }
    
    public static function checkHoneypot($honeypot_value) {
        return empty($honeypot_value);
    }
}

// Validation class
class BookingValidator {
    public static function validateRequired($data, $required_fields) {
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return "الحقل '{$field}' مطلوب.";
            }
        }
        return true;
    }
    
    public static function validateAppointmentDate($date, $working_hours) {
        $appointment_date = new DateTime($date);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        // Check if date is in the past
        if ($appointment_date < $today) {
            return 'لا يمكن حجز موعد في تاريخ سابق.';
        }
        
        // Check if date is too far in the future (max 3 months)
        $max_date = clone $today;
        $max_date->add(new DateInterval('P3M'));
        if ($appointment_date > $max_date) {
            return 'لا يمكن حجز موعد أكثر من 3 أشهر مقدماً.';
        }
        
        // Check if it's a closed day
        $day_of_week = $appointment_date->format('w');
        if (in_array($day_of_week, $working_hours['closed_days'])) {
            return 'المختبر مغلق في هذا اليوم.';
        }
        
        return true;
    }
    
    public static function validateAppointmentTime($time, $working_hours) {
        $start_time = DateTime::createFromFormat('H:i', $working_hours['start']);
        $end_time = DateTime::createFromFormat('H:i', $working_hours['end']);
        $appointment_time = DateTime::createFromFormat('H:i', $time);
        
        if ($appointment_time < $start_time || $appointment_time > $end_time) {
            return "ساعات العمل من {$working_hours['start']} إلى {$working_hours['end']}.";
        }
        
        return true;
    }
    
    public static function validateService($service, $available_services) {
        if (!array_key_exists($service, $available_services)) {
            return 'الخدمة المحددة غير متوفرة.';
        }
        return true;
    }
    
    public static function validateBranch($branch, $available_branches) {
        if (!array_key_exists($branch, $available_branches)) {
            return 'الفرع المحدد غير متوفر.';
        }
        return true;
    }
}

// Email class
class BookingEmailSender {
    public static function send($to, $subject, $booking_data, $from_email) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: مختبرات التضامن الدولية <' . $from_email . '>',
            'Reply-To: ' . $booking_data['email'],
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 3',
            'Date: ' . date('r')
        ];
        
        $email_body = self::buildBookingEmailTemplate($booking_data);
        
        return mail($to, $subject, $email_body, implode("\r\n", $headers));
    }
    
    public static function sendConfirmationToClient($booking_data, $from_email) {
        $subject = 'تأكيد حجز موعد - مختبرات التضامن الدولية';
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: مختبرات التضامن الدولية <' . $from_email . '>',
            'X-Mailer: PHP/' . phpversion(),
            'Date: ' . date('r')
        ];
        
        $email_body = self::buildClientConfirmationTemplate($booking_data);
        
        return mail($booking_data['email'], $subject, $email_body, implode("\r\n", $headers));
    }
    
    private static function buildBookingEmailTemplate($data) {
        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>حجز موعد جديد - مختبرات التضامن الدولية</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; direction: rtl; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2c5aa0 0%, #4a7bc8 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .booking-details { background: white; padding: 20px; border-radius: 8px; border-right: 4px solid #2c5aa0; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; margin: 15px 0; padding: 10px 0; border-bottom: 1px solid #eee; }
                .detail-label { font-weight: bold; color: #2c5aa0; }
                .detail-value { color: #333; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
                .urgent { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>مختبرات التضامن الدولية</h1>
                    <p>حجز موعد جديد</p>
                </div>
                <div class='content'>
                    <div class='urgent'>
                        <strong>تنبيه:</strong> يرجى التواصل مع العميل لتأكيد الموعد في أقرب وقت ممكن.
                    </div>
                    <div class='booking-details'>
                        <h3>تفاصيل الحجز:</h3>
                        <div class='detail-row'>
                            <span class='detail-label'>الاسم:</span>
                            <span class='detail-value'>{$data['name']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>رقم الهاتف:</span>
                            <span class='detail-value'>{$data['phone']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>البريد الإلكتروني:</span>
                            <span class='detail-value'>{$data['email']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>التاريخ المطلوب:</span>
                            <span class='detail-value'>{$data['date']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>الوقت المطلوب:</span>
                            <span class='detail-value'>{$data['time']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>الخدمة المطلوبة:</span>
                            <span class='detail-value'>{$data['service_name']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>الفرع المطلوب:</span>
                            <span class='detail-value'>{$data['branch_name']}</span>
                        </div>";
        
        if (!empty($data['notes'])) {
            $email_body .= "
                        <div class='detail-row'>
                            <span class='detail-label'>ملاحظات:</span>
                            <span class='detail-value'>" . nl2br(htmlspecialchars($data['notes'])) . "</span>
                        </div>";
        }
        
        $email_body .= "
                        <div class='detail-row'>
                            <span class='detail-label'>تاريخ الحجز:</span>
                            <span class='detail-value'>" . date('Y-m-d H:i:s') . "</span>
                        </div>
                    </div>
                </div>
                <div class='footer'>
                    <p>يرجى التواصل مع العميل على الرقم: {$data['phone']}</p>
                    <p>أو عبر البريد الإلكتروني: {$data['email']}</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $email_body;
    }
    
    private static function buildClientConfirmationTemplate($data) {
        return "
        <!DOCTYPE html>
        <html dir='rtl' lang='ar'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>تأكيد حجز الموعد - مختبرات التضامن الدولية</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; direction: rtl; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2c5aa0 0%, #4a7bc8 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .booking-summary { background: white; padding: 20px; border-radius: 8px; border-right: 4px solid #28a745; margin: 20px 0; }
                .detail-row { margin: 10px 0; }
                .detail-label { font-weight: bold; color: #2c5aa0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
                .contact-info { background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .important { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>مختبرات التضامن الدولية</h1>
                    <p>تأكيد حجز الموعد</p>
                </div>
                <div class='content'>
                    <h2>عزيزي/عزيزتي {$data['name']}</h2>
                    <p>شكراً لك على اختيار مختبرات التضامن الدولية. تم استلام طلب حجز موعدك بنجاح.</p>
                    
                    <div class='booking-summary'>
                        <h3>ملخص الموعد:</h3>
                        <div class='detail-row'>
                            <span class='detail-label'>التاريخ:</span> {$data['date']}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>الوقت:</span> {$data['time']}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>الخدمة:</span> {$data['service_name']}
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>الفرع:</span> {$data['branch_name']}
                        </div>
                    </div>
                    
                    <div class='important'>
                        <strong>مهم:</strong> سيتم التواصل معك خلال 24 ساعة لتأكيد الموعد وتحديد التفاصيل النهائية.
                    </div>
                    
                    <div class='contact-info'>
                        <h4>معلومات التواصل:</h4>
                        <p><strong>الهاتف:</strong> +967 4 123456</p>
                        <p><strong>البريد الإلكتروني:</strong> info@tadamun-labs.com</p>
                        <p><strong>العنوان:</strong> شارع جمال مع تقاطع شارع التحرير، تعز</p>
                    </div>
                    
                    <h4>تعليمات مهمة قبل الحضور:</h4>
                    <ul>
                        <li>يرجى الحضور قبل 15 دقيقة من موعدك</li>
                        <li>إحضار بطاقة الهوية الشخصية</li>
                        <li>في حالة فحوصات الدم، يفضل الصيام لمدة 8-12 ساعة</li>
                        <li>إحضار أي تقارير طبية سابقة إن وجدت</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>نتطلع لخدمتك في مختبرات التضامن الدولية</p>
                    <p>دقة في التشخيص، سرعة في الأداء</p>
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
        BookingResponse::error('طريقة الطلب غير صحيحة.', 405);
    }
    
    // Get and sanitize input data
    $name = BookingSecurity::sanitizeInput($_POST['name'] ?? '');
    $phone = BookingSecurity::sanitizeInput($_POST['phone'] ?? '');
    $email = BookingSecurity::sanitizeInput($_POST['email'] ?? '');
    $date = BookingSecurity::sanitizeInput($_POST['date'] ?? '');
    $time = BookingSecurity::sanitizeInput($_POST['time'] ?? '');
    $service = BookingSecurity::sanitizeInput($_POST['service'] ?? '');
    $branch = BookingSecurity::sanitizeInput($_POST['branch'] ?? 'main');
    $notes = BookingSecurity::sanitizeInput($_POST['notes'] ?? '');
    $honeypot = $_POST[$config['honeypot_field']] ?? '';
    
    // Check honeypot (anti-spam)
    if (!BookingSecurity::checkHoneypot($honeypot)) {
        BookingResponse::error('تم اكتشاف محاولة حجز غير مشروعة.', 403);
    }
    
    // Validate required fields
    $validation = BookingValidator::validateRequired($_POST, $config['required_fields']);
    if ($validation !== true) {
        BookingResponse::error($validation);
    }
    
    // Validate email
    if (!BookingSecurity::validateEmail($email)) {
        BookingResponse::error('البريد الإلكتروني غير صحيح.');
    }
    
    // Validate phone
    if (!BookingSecurity::validatePhone($phone)) {
        BookingResponse::error('رقم الهاتف غير صحيح.');
    }
    
    // Validate date format
    if (!BookingSecurity::validateDate($date)) {
        BookingResponse::error('تنسيق التاريخ غير صحيح.');
    }
    
    // Validate time format
    if (!BookingSecurity::validateTime($time)) {
        BookingResponse::error('تنسيق الوقت غير صحيح.');
    }
    
    // Validate appointment date
    $date_validation = BookingValidator::validateAppointmentDate($date, $config['working_hours']);
    if ($date_validation !== true) {
        BookingResponse::error($date_validation);
    }
    
    // Validate appointment time
    $time_validation = BookingValidator::validateAppointmentTime($time, $config['working_hours']);
    if ($time_validation !== true) {
        BookingResponse::error($time_validation);
    }
    
    // Validate service
    $service_validation = BookingValidator::validateService($service, $config['services']);
    if ($service_validation !== true) {
        BookingResponse::error($service_validation);
    }
    
    // Validate branch
    $branch_validation = BookingValidator::validateBranch($branch, $config['branches']);
    if ($branch_validation !== true) {
        BookingResponse::error($branch_validation);
    }
    
    // Prepare booking data
    $booking_data = [
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'date' => $date,
        'time' => $time,
        'service' => $service,
        'service_name' => $config['services'][$service],
        'branch' => $branch,
        'branch_name' => $config['branches'][$branch],
        'notes' => $notes
    ];
    
    // Prepare email subject
    $email_subject = $config['subject_prefix'] . $name . ' - ' . $date . ' ' . $time;
    
    // Send booking email to lab
    $email_sent = BookingEmailSender::send(
        $config['to_email'],
        $email_subject,
        $booking_data,
        $config['from_email']
    );
    
    if ($email_sent) {
        // Send confirmation email to client
        BookingEmailSender::sendConfirmationToClient($booking_data, $config['from_email']);
        
        // Log successful booking
        error_log("Appointment booked successfully: {$name} ({$email}) - {$date} {$time}");
        BookingResponse::success();
    } else {
        // Log email failure
        error_log("Failed to send booking email: {$name} ({$email}) - {$date} {$time}");
        BookingResponse::error('فشل في إرسال البريد الإلكتروني. يرجى المحاولة لاحقاً.', 500);
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Booking form error: " . $e->getMessage());
    BookingResponse::error('حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.', 500);
}
?>

