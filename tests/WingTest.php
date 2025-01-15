<?php

use PHPUnit\Framework\TestCase;
use Wing\Wing;

class WingTest extends TestCase
{
    protected string $viewsPath;
    protected string $cachePath;
    protected Wing $wing;

    protected function setUp(): void
    {
        $this->viewsPath = __DIR__ . '/views';
        $this->cachePath = __DIR__ . '/cache';

        // Создаем тестовые папки
        if (!is_dir($this->viewsPath)) {
            mkdir($this->viewsPath, 0777, true);
        }

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }

        $this->wing = new Wing($this->viewsPath, $this->cachePath);
    }

    public function testRenderBasicTemplate()
    {
        // Создаем простой шаблон
        $templateContent = '<h1>{{ $title }}</h1>';
        file_put_contents($this->viewsPath . '/test.wing', $templateContent);

        // Рендерим шаблон
        $output = $this->wing->with(['title' => 'Hello, Wing!'])->render('test');

        // Проверяем результат
        $this->assertEquals('<h1>Hello, Wing!</h1>', $output);
    }

    public function testIncludeDirective()
    {
        // Создаем основной шаблон и инклюд
        file_put_contents($this->viewsPath . '/header.wing', '<header>Header Content</header>');
        file_put_contents($this->viewsPath . '/main.wing', '@include("header")<h1>Main Content</h1>');

        $output = $this->wing->render('main');

        $this->assertEquals('<header>Header Content</header><h1>Main Content</h1>', $output);
    }

    protected function tearDown(): void
    {
        // Удаляем тестовые файлы
        array_map('unlink', glob("{$this->viewsPath}/*.wing"));
        array_map('unlink', glob("{$this->cachePath}/*.php"));
        rmdir($this->viewsPath);
        rmdir($this->cachePath);
    }
}
