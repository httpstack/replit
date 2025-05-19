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
        // var_dump($this->template->fileLoader->findFile('style.css', null, 'css')); // ;
        $this->template->injectView($view, 'viewContent');
        $this->template->processData('template');
        $this->response->setBody($this->template->saveHTML());
    }
}
?>