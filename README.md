# Auto Mailer

## Project Description

Auto Mailer is a PHP-based bulk email tool for sending personalized emails to a list of recipients. Recipients can be supplied via an uploaded CSV or XLSX file, or typed in manually, and each email is personalized with a greeting generated from the recipient's name. Before anything is sent, the tool shows a full preview of every email it's about to send, so nothing goes out unreviewed. Sending happens through ProtonMail's SMTP servers via PHPMailer, with a randomized delay between each send to avoid tripping spam/rate-limit detection.

## Technologies Used

- PHP — core application logic
- PHPMailer — SMTP email delivery
- PhpSpreadsheet — reading XLSX recipient files
- phpdotenv — loading SMTP credentials from a .env file rather than hardcoding them in source
- Composer — dependency management
- Bootstrap 5 (via CDN) — form styling and the preview/confirmation modals
- SheetJS (xlsx.js) (via CDN) — client-side XLSX parsing, used to build the email preview in the browser before anything is submitted
- Vanilla JavaScript — the preview flow and greeting-generation logic on the client side
- ProtonMail SMTP — the mail server actually used to send

## Features

- Compose a subject, message body, and optional CC recipient
- Supply recipients via CSV upload, XLSX upload, or a manual Name,Email text box (one per line)
- Automatic, per-recipient greeting generation that detects a title (Dr./Mr./Ms./Mrs./Prof.) in the recipient's name and formats accordingly
- Full preview of every email before sending, showing the exact subject, recipients, CC, and personalized body for each one — with - the option to skip the preview and send immediately if preferred
- Randomized delay (5–30 seconds) between each send to avoid triggering spam/rate-limit detection
- SMTP credentials loaded from a .env file, kept out of source control

## Getting Started

You need a paid Proton mail account to actually send emails. However, you can still run the application in your local browser. 
Steps: 
1. Installation: composer install
2. Create a .env file in the project root:
   - SMTP_HOST=smtp.protonmail.ch
   - SMTP_PORT=587
   - SMTP_USERNAME=your_email@yourdomain.com
   - SMTP_TOKEN=your_protonmail_bridge_app_password
4. Run locally: php -S localhost:8000
5. Open http://localhost:8000/index.php in your browser, or launch start_mailer.bat (Windows) or start_mailer.command (Mac). 

## Usage
1. Fill in the Subject, Message, and optional CC fields.
2. Add recipients — upload a CSV/XLSX file (with Name in the first column and Email in the second), or type them manually as Name,Email, one per line.
3. Submit the form. You'll be asked whether you want to preview the emails first.
4. If you preview, review every generated email, then click "Looks Good, Send" to actually send them.
5. Emails are sent one at a time with a random delay between each — for a large list, this will take a while by design.
