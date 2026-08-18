@echo off
REM Go to the folder containing index.php
cd /d "C:\Users\franceshogg\Documents\auto_mailer" || exit /b

REM Confirm current directory
echo Starting local PHP server in %cd%...

REM Kill any previous PHP servers on port 8000 (prevents "Address already in use" errors)
for /f "tokens=5" %%a in ('netstat -aon ^| find ":8000" ^| find "LISTENING"') do taskkill /PID %%a /F >nul 2>&1

REM Start the PHP built-in server in the background
start "" php -S localhost:8000 >nul 2>&1

REM Wait a moment to ensure the server starts
timeout /t 2 >nul

REM Open the default browser to the index page
start "" "http://localhost:8000/index.php"

echo ✅ Server started and browser opened!
pause
