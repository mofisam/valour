<?php
declare(strict_types=1);

/**
 * Create (or reset) an administrator from the command line.
 *
 *   php bin/create-admin.php "Full Name" admin@example.com "StrongPassword" ["Company Name"]
 *
 * The company name is optional here: you can always set or change it later in Settings > Business details.
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
spl_autoload_register(function (string $c): void {
    if (strncmp($c, 'App\\', 4) === 0) {
        $f = APP_PATH . '/' . str_replace('\\', '/', substr($c, 4)) . '.php';
        if (is_file($f)) require $f;
    }
});
require APP_PATH . '/Helpers/functions.php';

[$script, $name, $email, $password, $company] = array_pad($argv, 5, null);
if (!$name || !$email || !$password) {
    fwrite(STDERR, "Usage: php bin/create-admin.php \"Full Name\" email@example.com \"Password\"\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    fwrite(STDERR, "Use a valid email and a password of at least 8 characters.\n");
    exit(1);
}

use App\Models\User;

$hash = password_hash($password, PASSWORD_DEFAULT);
$existing = User::findByEmail($email);

if ($existing) {
    User::update((int) $existing['id'], ['name' => $name, 'password_hash' => $hash, 'role_id' => 1, 'is_active' => 1]);
    echo "Updated existing user and made them an administrator: $email\n";
} else {
    User::insert(['name' => $name, 'email' => strtolower($email), 'password_hash' => $hash, 'role_id' => 1, 'is_active' => 1]);
    echo "Administrator created: $email\n";
}

if ($company) {
    App\Models\Setting::set('business_name', $company);
    echo "Company name saved: $company\n";
}
