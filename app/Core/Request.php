<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    private array $data;

    public function __construct()
    {
        $this->data = array_merge($_GET, $_POST);

        // JSON bodies
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $json = json_decode(file_get_contents('php://input') ?: '', true);
            if (is_array($json)) {
                $this->data = array_merge($this->data, $json);
            }
        }
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        return current_path();
    }

    public function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    /** Trimmed string/array input. HTML is NOT stripped here; escape on output with e(). */
    public function input(string $key, mixed $default = null): mixed
    {
        return isset($this->data[$key]) ? $this->clean($this->data[$key]) : $default;
    }

    public function all(): array
    {
        return $this->clean($this->data);
    }

    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    public function file(string $key): ?array
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES[$key] : null;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function clean(mixed $v): mixed
    {
        if (is_array($v)) {
            return array_map(fn($x) => $this->clean($x), $v);
        }
        return is_string($v) ? trim($v) : $v;
    }
}
