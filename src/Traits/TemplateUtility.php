<?php
namespace Framework\Traits;
trait TemplateUtility
{
    /**
     * Get the base template path.
     *
     * @return string
     */
    public function applyTemplate(string $view): void
    {
        //debug($this->template->fileLoader->findFile('style.css', null, 'css')); // ;

    }
}
?>