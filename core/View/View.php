<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* View
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees\View;

class View
{
    private string $viewsPath;
    private array $data = [];
    private array $sections = [];
    private ?string $currentSection = null;
    private ?string $layout = null;

    public function __construct(string $viewsPath)
    {
        $this->viewsPath = rtrim($viewsPath, '/');
    }

    /**
     * Render a view
     */
    public function render(string $view, array $data = []): string
    {
        $this->data = array_merge($this->data, $data);

        $viewPath = $this->findViewPath($view);

        if (!$viewPath) {
            throw new \RuntimeException("View [{$view}] not found.");
        }

        $content = $this->renderView($viewPath, $this->data);

        if ($this->layout) {
            $layoutPath = $this->findViewPath($this->layout);
            if (!$layoutPath) {
                throw new \RuntimeException("Layout [{$this->layout}] not found.");
            }
            $this->sections['content'] = $content;
            $content = $this->renderView($layoutPath, $this->data);
            $this->layout = null;
            $this->sections = [];
        }

        return $content;
    }

    /**
     * Find view file path
     */
    private function findViewPath(string $view): ?string
    {
        // Convert dot notation to path (e.g., "blog.index" -> "blog/index.php")
        $path = $this->viewsPath . '/' . str_replace('.', '/', $view) . '.php';

        return file_exists($path) ? $path : null;
    }

    /**
     * Render a view file
     */
    private function renderView(string $path, array $data): string
    {
        extract($data);

        ob_start();
        include $path;
        return ob_get_clean();
    }

    /**
     * Start a section
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End a section
     */
    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    /**
     * Yield a section
     */
    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Set layout
     */
    public function layout(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Escape HTML
     */
    public function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Include a partial view
     */
    public function include(string $view, array $data = []): void
    {
        echo $this->render($view, array_merge($this->data, $data));
    }

    /**
     * Add global data
     */
    public function share(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Create a view instance
     */
    public static function make(string $viewsPath, string $view, array $data = []): string
    {
        $instance = new self($viewsPath);
        return $instance->render($view, $data);
    }
}
