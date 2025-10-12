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
        
        $content = $this->compileEchos($content);
        $content = $this->compileConditionals($content);
        $content = $this->compileLoops($content);
        $content = $this->compileIncludes($content);
        $content = $this->compileLayouts($content);
        $content = $this->compileCsrf($content);
        
        file_put_contents($compiled, $content);
    }
    
    private function compileEchos(string $content): string
    {
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?php echo htmlspecialchars($1 ?? \'\', ENT_QUOTES, \'UTF-8\'); ?>', $content);
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?php echo $1; ?>', $content);
        
        return $content;
    }
    
    private function compileConditionals(string $content): string
    {
        $content = preg_replace('/\@if\s*\((.+?)\)/', '<?php if ($1): ?>', $content);
        $content = preg_replace('/\@elseif\s*\((.+?)\)/', '<?php elseif ($1): ?>', $content);
        $content = preg_replace('/\@else/', '<?php else: ?>', $content);
        $content = preg_replace('/\@endif/', '<?php endif; ?>', $content);
        $content = preg_replace('/\@unless\s*\((.+?)\)/', '<?php if (!($1)): ?>', $content);
        $content = preg_replace('/\@endunless/', '<?php endif; ?>', $content);
        
        return $content;
    }
    
    private function compileLoops(string $content): string
    {
        $content = preg_replace('/\@foreach\s*\((.+?)\)/', '<?php foreach ($1): ?>', $content);
        $content = preg_replace('/\@endforeach/', '<?php endforeach; ?>', $content);
        $content = preg_replace('/\@for\s*\((.+?)\)/', '<?php for ($1): ?>', $content);
        $content = preg_replace('/\@endfor/', '<?php endfor; ?>', $content);
        $content = preg_replace('/\@while\s*\((.+?)\)/', '<?php while ($1): ?>', $content);
        $content = preg_replace('/\@endwhile/', '<?php endwhile; ?>', $content);
        
        return $content;
    }
    
    private function compileIncludes(string $content): string
    {
        return preg_replace_callback(
            '/\@include\s*\([\'"](.+?)[\'"]\s*,?\s*(\[.+?\])?\)/',
            function ($matches) {
                $view = $matches[1];
                $data = $matches[2] ?? '[]';
                return "<?php echo \$this->render('{$view}', {$data}); ?>";
            },
            $content
        );
    }
    
    private function compileLayouts(string $content): string
    {
        $content = preg_replace('/\@extends\s*\([\'"](.+?)[\'"]\)/', '<?php $__layout = \'$1\'; ?>', $content);
        $content = preg_replace('/\@section\s*\([\'"](.+?)[\'"]\)/', '<?php $__sections[\'$1\'] = ob_start(); ?>', $content);
        $content = preg_replace('/\@endsection/', '<?php $__sections[array_key_last($__sections)] = ob_get_clean(); ?>', $content);
        $content = preg_replace('/\@yield\s*\([\'"](.+?)[\'"]\s*,?\s*[\'"]?(.*?)[\'"]?\)/', '<?php echo $__sections[\'$1\'] ?? \'$2\'; ?>', $content);
        
        return $content;
    }
    
    private function compileCsrf(string $content): string
    {
        return preg_replace('/\@csrf/', '<?php echo \'<input type="hidden" name="_token" value="\' . ($_SESSION[\'csrf_token\'] ?? \'\') . \'">\'; ?>', $content);
    }
    
    private function renderCompiled(string $compiled, array $data): string
    {
        extract($data);
        
        ob_start();
        include $compiled;
        return ob_get_clean();
    }
    
    private function renderDirect(string $viewFile, array $data): string
    {
        extract($data);
        
        ob_start();
        include $viewFile;
        return ob_get_clean();
    }
}