<?php

declare(strict_types=1);

namespace Trees\View;

class View
{
    private $engine;
    private $view;
    private $data;
    
    public function __construct(ViewEngine $engine, string $view, array $data = [])
    {
        $this->engine = $engine;
        $this->view = $view;
        $this->data = $data;
    }
    
    public function with(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }
    
    public function render(): string
    {
        return $this->engine->render($this->view, $this->data);
    }
    
    public function __toString(): string
    {
        return $this->render();
    }
}