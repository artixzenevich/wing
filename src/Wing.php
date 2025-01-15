<?php

namespace Wing;

class Wing
{
    protected string $viewsPath;
    protected string $cachePath;
    protected array $data = [];

    public function __construct(string $viewsPath, string $cachePath)
    {
        $this->viewsPath = rtrim($viewsPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
    }

    // Передача данных в шаблон
    public function with(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    // Основной метод рендера
    public function render(string $template): string
    {
        $templatePath = "{$this->viewsPath}/{$template}.wing";
        $cachedFile = "{$this->cachePath}/" . md5($template) . '.php';

        if (!file_exists($cachedFile) || filemtime($templatePath) > filemtime($cachedFile)) {
            $content = file_get_contents($templatePath);
            $compiledContent = $this->compile($content);
            file_put_contents($cachedFile, $compiledContent);
        }

        // Включение скомпилированного шаблона
        ob_start();
        extract($this->data);
        include $cachedFile;
        return ob_get_clean();
    }

    // Компиляция шаблона
    protected function compile(string $content): string
    {
        // Поддержка директив
        $content = preg_replace('/@if\s*\((.*?)\)/', '<?php if ($1): ?>', $content);
        $content = preg_replace('/@elseif\s*\((.*?)\)/', '<?php elseif ($1): ?>', $content);
        $content = preg_replace('/@else/', '<?php else: ?>', $content);
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);

        $content = preg_replace('/@foreach\s*\((.*?)\)/', '<?php foreach ($1): ?>', $content);
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);

        $content = preg_replace('/@include\s*\((.*?)\)/', '<?php include $this->getTemplatePath($1); ?>', $content);

        // Подключение функционала Битрикс
        $content = preg_replace('/@component\((.*?)\)/', '<?php $APPLICATION->IncludeComponent($1); ?>', $content);
        $content = preg_replace('/@asset\((.*?)\)/', '<?php echo \Bitrix\Main\Page\Asset::getInstance()->addString($1); ?>', $content);

        // Переменные {{ $var }}
        $content = preg_replace('/{{\s*(.+?)\s*}}/', '<?php echo htmlspecialchars($1); ?>', $content);

        return $content;
    }

    // Метод для разрешения путей
    protected function getTemplatePath(string $template): string
	{
		return rtrim($this->viewsPath, '/') . '/' . $template . '.wing';
	}

}
