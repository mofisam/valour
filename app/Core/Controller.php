<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    protected function view(string $view, array $data = [], ?string $layout = 'app'): void
    {
        View::render($view, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirect(string $path): never
    {
        redirect($path);
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    /** Validate, or flash errors + old input and go back. */
    protected function validate(array $rules, array $labels = []): array
    {
        $data = $this->request->all();
        $errors = Validator::validate($data, $rules, $labels);
        if ($errors) {
            Session::flashErrors($errors, $data);
            back('/');
        }
        return $data;
    }
}
