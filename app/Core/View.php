<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    /** @var string[] scripts queued by views; printed by the layout */
    private static array $scripts = [];

    /** Queue a script: 'jobs.js' (from assets/js) or a full https:// URL. */
    public static function script(string $src): void
    {
        if (!in_array($src, self::$scripts, true)) {
            self::$scripts[] = $src;
        }
    }

    public static function scripts(): array
    {
        return self::$scripts;
    }

    /** Render app/Views/{$view}.php inside layouts/{$layout}.php (null = no layout). */
    public static function render(string $view, array $data = [], ?string $layout = 'app'): void
    {
        $content = self::capture(APP_PATH . "/Views/{$view}.php", $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::capture(APP_PATH . "/Views/layouts/{$layout}.php", $data + ['content' => $content]);
    }

    public static function partial(string $name, array $data = []): void
    {
        echo self::capture(APP_PATH . "/Views/{$name}.php", $data);
    }

    private static function capture(string $file, array $data): string
    {
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $file");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
