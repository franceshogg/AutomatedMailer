<?php
// bulk_mailer.php

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

// --- Config ---
$delaySeconds = 10;

//Load .env
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__, '.env');
$dotenv->load();
require_once __DIR__ . '/vendor/autoload.php';

// SMTP settings (keep token in ENV for security)
$smtpHost = $_ENV['SMTP_HOST'] ?? null;//'smtp.protonmail.ch';
$smtpPort = $_ENV['SMTP_PORT'] ?? null;//587;
$smtpUsername = $_ENV['SMTP_USERNAME'] ?? null;//'franceshogg@francesorg.com';
$smtpToken = $_ENV['SMTP_TOKEN'] ?? null;//'GWPCEWBNRYQC5CFR'; 

$messageTemplateFile = 'message.txt';
$excelFile = 'recipients.xlsx';

// --- Load message ---
if (!empty($_POST['message'])) {
    $messageTemplate = $_POST['message'];
} else {
    $messageTemplate = file_get_contents($messageTemplateFile);
    if ($messageTemplate === false) {
        die("Error: Could not read message template.\n");
    }
}

// --- ADDED: Get CC email if provided ---
$ccEmail = !empty($_POST['cc_email']) ? trim($_POST['cc_email']) : null;
// --- END ADDED ---

// --- Handle recipients ---
$recipients = [];

// 1. Uploaded file
if (isset($_FILES['recipients']) && $_FILES['recipients']['error'] === 0) {
    $filePath = $_FILES['recipients']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['recipients']['name'], PATHINFO_EXTENSION));

    if ($ext === 'csv') {
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) >= 2) {
                    $recipients[] = ['name' => trim($data[0]), 'email' => trim($data[1])];
                }
            }
            fclose($handle);
        }
    } elseif ($ext === 'xlsx') {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        foreach ($rows as $i => $row) {
            if ($i === 1 && stripos($row['A'], 'name') !== false) continue;
            $recipients[] = ['name' => trim($row['A']), 'email' => trim($row['C'])];
        }
    }
}

// 2. Manual input via POST
if (!empty($_POST['recipients_manual'])) {
    $lines = explode("\n", $_POST['recipients_manual']);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $parts = explode(',', $line);
        if (count($parts) >= 2) {
            $recipients[] = ['name' => trim($parts[0]), 'email' => trim($parts[1])];
        }
    }
}

// 3. Fallback to default XLSX file
if (empty($recipients)) {
    if (!file_exists($excelFile)) {
        die("No recipients provided and default file missing.\n");
    }
    $spreadsheet = IOFactory::load($excelFile);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
    foreach ($rows as $i => $row) {
        if ($i === 1 && stripos($row['A'], 'name') !== false) continue;
        $recipients[] = ['name' => trim($row['A']), 'email' => trim($row['C'])];
    }
}

// --- Setup PHPMailer ---
$mail = new PHPMailer(true);
$mail->SMTPDebug = 2; // debug output for localhost
$mail->Debugoutput = 'html';
$mail->isSMTP();
$mail->Host = $smtpHost;
$mail->Port = $smtpPort;
$mail->SMTPAuth = true;
$mail->Username = $smtpUsername;
$mail->Password = $smtpToken;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->setFrom($smtpUsername, 'AIBRT Communications');

//Turns off debug messages
$mail->SMTPDebug = 0; 

// --- Loop and send ---
$subject = !empty($_POST['subject']) ? $_POST['subject'] : 'Default Subject';

foreach ($recipients as $recipient) {
    $fullName = $recipient['name'];
    $email = $recipient['email'];
    if (empty($fullName) || empty($email)) continue;

    $greeting = makeGreeting($fullName);
    $personalizedMessage = $greeting . "\n\n" . $messageTemplate;

    try {
        $mail->clearAddresses();
        $mail->clearCCs(); // ADDED: Clear CC addresses from previous iteration
        $mail->addAddress($email, $fullName);
        
        // ADDED: Add CC if provided and valid
        if ($ccEmail && filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addCC($ccEmail); // ADDED: Add the CC recipient
        }
        // END ADDED
        
        $mail->Subject = $subject;
        $mail->Body = $personalizedMessage;

        $mail->send();
        $ccInfo = ($ccEmail) ? " (CC: $ccEmail)" : ""; // ADDED: Build CC info string for output
        echo "✅ Sent to $fullName <$email>$ccInfo<br>"; // MODIFIED: Added $ccInfo to display CC in output
    } catch (Exception $e) {
        echo "❌ Error sending to $fullName <$email>: {$mail->ErrorInfo}<br>";
    }
    //sleep($delaySeconds);
    sleep(rand(5, 30));
}

echo "<br>All emails processed.";

// --- Helper ---
function makeGreeting($fullName) {
    $parts = explode(' ', $fullName);
    if (count($parts) >= 2) {
        $title = $parts[0];
        $lastName = end($parts);
        if (preg_match('/^Dr\.?|Prof\.?|Mr\.?|Ms\.?|Mrs\.?/i', $title)) {
            return "Dear $title $lastName,";
        }
    }
    return "Dear $fullName,";
}
