<?php
declare(strict_types=1);

namespace App\Core;

class App
{
    public function run(): void
    {
        require_once APP_PATH . '/Helpers/constants.php';

        date_default_timezone_set(config('app.timezone', 'Africa/Lagos'));
        mb_internal_encoding('UTF-8');
        $this->registerErrorHandling();

        Session::start();

        $router = new Router();
        require BASE_PATH . '/config/routes.php';   // defines routes on $router

        $router->dispatch(new Request());
    }

    private function registerErrorHandling(): void
    {
        $debug = (bool) config('app.debug', false);
        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);

        set_exception_handler(function (\Throwable $e) use ($debug): void {
            $line = sprintf("[%s] %s: %s in %s:%d\n%s\n", date('c'), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
            @file_put_contents(BASE_PATH . '/storage/logs/app.log', $line, FILE_APPEND);

            if (!headers_sent()) {
                http_response_code(500);
            }
            if ($debug) {
                echo '<pre style="padding:16px;white-space:pre-wrap">' . htmlspecialchars($line) . '</pre>';
                return;
            }
            View::render('errors/500', [], 'auth');
        });
    }
}
