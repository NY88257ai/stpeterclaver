<?php
// contact-handler.php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

$recaptcha = $_POST['g-recaptcha-response'] ?? '';
if (!$recaptcha) {
  http_response_code(400);
  exit('Missing reCAPTCHA');
}

// 1. Verify reCAPTCHA (server side)
$secret = getenv('RECAPTCHA_SECRET');
$resp = file_get_contents(
  'https://www.google.com/recaptcha/api/siteverify?secret=' .
  urlencode($secret) . '&response=' . urlencode($recaptcha)
);
$data = json_decode($resp, true);
if (empty($data['success'])) {
  http_response_code(400);
  exit('reCAPTCHA failed');
}

// 2. Basic validation
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $subject === '' || $message === '') {
  http_response_code(400);
  exit('Invalid input');
}

// 3. Send email (configure in env)
$to      = getenv('CONTACT_TO') ?: 'ny88257@protonmail.com';
$headers = "From: {$name} <{$email}>\r\n" .
           "Reply-To: {$email}\r\n" .
           "Content-Type: text/plain; charset=UTF-8\r\n";

$body = "Name: {$name}\nEmail: {$email}\nSubject: {$subject}\n\n{$message}\n";

if (!mail($to, "[Website Contact] {$subject}", $body, $headers)) {
  http_response_code(500);
  exit('Unable to send');
}

// 4. Redirect or JSON
header('Location: /thank-you.html');
