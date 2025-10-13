<?php

declare(strict_types=1);

namespace Trees\View;

class ViewEngine
{
    private $viewPath;
    private $cachePath;
    private $cacheEnabled;
    private $sharedData = [];

    public function __construct(string $viewPath, string $cachePath, bool $cacheEnabled = true)
    {
        $this->viewPath = rtrim($viewPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
        $this->cacheEnabled = $cacheEnabled;

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function render(string $view, array $data = []): string
    {
        $data = array_merge($this->sharedData, $data);

        $viewFile = $this->getViewPath($view);

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View [{$view}] not found at {$viewFile}");
        }

        if ($this->cacheEnabled) {
            $compiled = $this->getCompiledPath($view);

            if (!file_exists($compiled) || filemtime($viewFile) > filemtime($compiled)) {
                $this->compile($viewFile, $compiled);
            }

            return $this->renderCompiled($compiled, $data);
        }

        return $this->renderDirect($viewFile, $data);
    }

    public function share(string $key, $value): void
    {
        $this->sharedData[$key] = $value;
    }

    private function getViewPath(string $view): string
    {
        $view = str_replace('.', '/', $view);
        return "{$this->viewPath}/{$view}.php";
    }

    private function getCompiledPath(string $view): string
    {
        return "{$this->cachePath}/" . md5($view) . '.php';
    }

    private function compile(string $viewFile, string $compiled): void
    {
        $content = file_get_contents($viewFile);

        $compiler = new ViewCompiler();
        $content = $compiler->compile($content);

        file_put_contents($compiled, $content);
    }

    private function renderCompiled(string $compiled, array $data): string
    {
        extract($data);

        // Initialize sections array
        $__sections = [];
        $__extends = null;

        ob_start();
        try {
            include $compiled;
            $childContent = ob_get_clean();

            // If template extends a layout, render the parent
            if (isset($__extends) && $__extends) {
                $parentView = $this->getViewPath($__extends);
                if (!file_exists($parentView)) {
                    throw new \RuntimeException("Parent view [{$__extends}] not found");
                }

                $parentCompiled = $this->getCompiledPath($__extends);
                if (!file_exists($parentCompiled) || filemtime($parentView) > filemtime($parentCompiled)) {
                    $this->compile($parentView, $parentCompiled);
                }

                // Render parent with sections from child
                ob_start();
                include $parentCompiled;
                return ob_get_clean();
            }

            return $childContent;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new \RuntimeException("Error rendering view: " . $e->getMessage() . "\nFile: " . $compiled, 0, $e);
        }
    }

    private function renderDirect(string $viewFile, array $data): string
    {
        extract($data);

        // Initialize sections array
        $__sections = [];
        $__extends = null;

        ob_start();
        try {
            include $viewFile;
            $childContent = ob_get_clean();

            // If template extends a layout, render the parent
            if (isset($__extends) && $__extends) {
                $parentView = $this->getViewPath($__extends);
                if (!file_exists($parentView)) {
                    throw new \RuntimeException("Parent view [{$__extends}] not found");
                }

                ob_start();
                include $parentView;
                return ob_get_clean();
            }

            return $childContent;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new \RuntimeException("Error rendering view: " . $e->getMessage(), 0, $e);
        }
    }
}
