<?php
// config.php

// Database credentials (you should ideally also store these in environment variables)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_NAME', 'your_database_name');

// SMTP credentials
define('SMTP_HOST', 'smtp.gmail.com');  // SMTP Host
define('SMTP_USERNAME', 'your-email@gmail.com'); // Your Gmail email address
define('SMTP_PASSWORD', 'your-app-password');    // Gmail App password (generated for SMTP)
define('SMTP_PORT', 587);                   // SMTP port (usually 587 for TLS)
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com'); // From email address
define('SMTP_FROM_NAME', 'ThreadFlow');      // From name for the email
