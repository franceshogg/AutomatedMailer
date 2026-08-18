#!/bin/bash

# Go to the folder containing index.php
cd /Users/franceshogg/Documents/auto_mailer || exit

# Confirm current directory
echo "Starting local PHP server in $(pwd)..."

# Kill any previous PHP servers on port 8000 (prevents "Address already in use" errors)
lsof -ti tcp:8000 | xargs kill -9 2>/dev/null

# Start the PHP built-in server in the background
php -S localhost:8000 > /dev/null 2>&1 &

# Wait a second to ensure the server starts
sleep 1

# Open the default browser to the index page
open "http://localhost:8000/index.php"

echo "✅ Server started and browser opened!"
