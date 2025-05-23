<?php

namespace Framework\Template;

use App\Models\TemplateModel;
use Framework\Core\Application;
use Framework\Exceptions\FrameworkException;
use Framework\FileSystem\FileLoader;
use Framework\Traits\DomUtility;
use Framework\Model\BaseModel;
use Framework\Container\Container;

/**
 * Template Engine.
 *
 * Handles template loading, rendering, and variable replacement
 * using DOM manipulation and string replacements
 */
class TemplateEngine extends \DOMDocument
{
    /**
     * Create a new template engine.
     *
     * @param FileLoader $fileLoader
     * @param string     $templateDir
     */
    use DomUtility;
    /**
     * File loader instance.
     */
    public FileLoader $fileLoader;

    /**
     * DOM manipulator.
     */


    /**
     * Template variables.
     */
    protected array $variables = [];

    /**
     * Template directory.
     */
    protected string $templateDir;

    /**
     * Template cache.
     */
    protected array $cache = [];
    protected string $baseUri;
    protected Container $container;
    public function __construct(
        FileLoader $fileLoader,
        TemplateModel $templateModel
    ) {
        parent::__construct('1.0', 'UTF-8');
        $this->container = app()->getContainer();
        //SET THE TEMPLATE MODEL
        $this->baseUri = $templateModel->getAttribute('baseUri');
        $this->templateDir = $templateModel->getAttribute('templateDir');
        $this->formatOutput = true;
        $this->preserveWhiteSpace = false;
        $this->registerNodeClass('DOMElement', \DOMElement::class);
        $this->fileLoader = $fileLoader;
        //LOAD THE ENGINE WITH THE REPLACEMENT DATA FROM THE MODEL
        $this->assign($templateModel->getAttributes());
        $this->xpath = new \DOMXPath($this);
    }

    public function loadAssets($assets)
    {
        $this->xpath = new \DOMXPath($this);
        $head = $this->getElementsByTagName('head')->item(0);
        //  var_dump($head);
        $body = $this->getElementsByTagName('body')->item(0);
        foreach ($assets as $asset) {
            $assetPath = $this->baseUri.$asset['filePath'];
            //debug($asset['filePath']);
            if (pathinfo($assetPath)['extension'] === 'css') {
                $link = $this->createElement('link');
                $link->setAttribute('rel', 'stylesheet');
                $link->setAttribute('href', $assetPath);
                $link->setAttribute('type', 'text/css');
                $head->appendChild($link);
            } elseif (pathinfo($assetPath)['extension'] === 'js') {
                $script = $this->createElement('script');
                $script->setAttribute('src', $assetPath);
                $script->setAttribute('type', 'text/javascript');
                $body->appendChild($script);
            } else {
                throw new FrameworkException("Asset not found: {$assetPath}");
            }
        }
    }
    public function setDataModel(BaseModel $model): void
    {
        $this->variables = array_merge($this->variables, $model->getAttributes());
    }
    /**
     * Assign variables to the template.
     *
     * @param string|array $key
     *
     * @return $this
     */
    public function assign($key, $value = null): self
    {
        if (is_array($key)) {
            $this->variables = array_merge($this->variables, $key);
        } else {
            $this->variables[$key] = $value;
        }

        return $this;
    }

    /**
     * Render a template with variables.
     *
     * @throws FrameworkException
     */
    public function render(string $template, array $variables = []): string
    {
        // Combine assigned variables with method variables
        $this->variables = array_merge($this->variables, $variables);

        // Load the template content
        $content = $this->loadTemplate($template);

        // Process the template
        return $this->processTemplate($content);
    }

    public function injectView(string $template, string $id): string
    {
        // Combine assigned variables with method variables
        $hostNode = $this->getElementById($id);

        $content = $this->loadTemplate($template);
        $fragment = $this->createFragment($content);
        if ($hostNode instanceof \DOMElement) {
            $hostNode->appendChild($fragment);
        } else {
            throw new FrameworkException('Invalid host node type: XPath must point to a DOMElement.');
        }
        $this->xpath = new \DOMXPath($this);

        // Process the template
        return '1';
    }

    /**
     * Load a template file.
     *
     * @throws FrameworkException
     */
    public function loadTemplate(string $template, bool $isBaseTemplate = false): string
    {
        // ho 'Loading template: '.$template.PHP_EOL;
        $templatePath = $this->resolveTemplatePath($template);

        if (isset($this->cache[$templatePath])) {
            return $this->cache[$templatePath];
        }

        if (!file_exists($templatePath)) {
            throw new FrameworkException("Template not found: {$templatePath}");
        }

        $content = file_get_contents($templatePath);
        $this->cache[$templatePath] = $content;
        //IF THIS IS THE BASE TEMPLATE THEN SET ITS HTML TO THE PARENT DOMDocument
        if($isBaseTemplate){
            $this->loadHTML($content);
        }
        
        return $content;
    }

    /**
     * Resolve the template path.
     */
    protected function resolveTemplatePath(string $template): string
    {
        // Add .html extension if not provided
        if (!preg_match('/\.(html|php|tpl)$/i', $template)) {
            $template .= '.html';
        }

        // If the template has an absolute path, use it
        if ($template[0] === '/' || preg_match('/^[a-zA-Z]:\\\/', $template)) {
            return $template;
        }

        // Otherwise, look in the template directory
        return $this->templateDir.'/'.$template;
    }

    /**
     * Process the template content.
     */
    protected function processTemplate(string $content): string
    {
        // Load the content into the DOM manipulator
        $this->loadHTML($content);

        // Process template includes
        $this->processIncludes();

        // Process data-template attributes
        $this->processDataTemplates();

        // Process data-view attributes
        $this->processDataViews();

        // Process data-model attributes
        $this->processDataModels();

        // Get the processed HTML
        $html = $this->saveHTML();

        // Process variable placeholders {{ var }}
        return $this->processVariables($html);
    }

    /**
     * Process template includes.
     */
    protected function processIncludes(): void
    {
        $includes = $this->findAll('[data-include]');

        foreach ($includes as $element) {
            if ($element instanceof \DOMElement) {
                $templateName = $element->getAttribute('data-include');
            } else {
                throw new FrameworkException("Invalid element type: 'data-include' attribute cannot be accessed.");
            }

            try {
                $includedContent = $this->loadTemplate($templateName);

                // Create a document fragment
                $fragment = $this->createFragment($includedContent);

                // Replace the element with the fragment
                if ($element->parentNode !== null) {
                    $element->parentNode->replaceChild($fragment, $element);
                }
            } catch (FrameworkException $e) {
                // If template not found, add an error comment
                $comment = $this->createComment("Include Error: {$e->getMessage()}");
                $element->parentNode->replaceChild($comment, $element);
            }
        }
    }
    public function processHandlebars()
    {
        // Get the current HTML as text
        $content = $this->saveHTML();

        // Replace all {{ key }} placeholders with their values
        $content = $this->processVariables($content);

        // Load the processed HTML back into the DOMDocument instance
        $this->loadHTML($content);
    }
    public function processData(string $level)
    {
        $selector = "[data-$level]";
        $elements = $this->findAll($selector);
        foreach ($elements as $element) {
            if ($element instanceof \DOMElement) {
                $dataKey = $element->getAttribute("data-$level");
            } else {
                throw new FrameworkException("Invalid element type: 'data-$level' attribute cannot be accessed.");
            }
            foreach ($this->variables as $key => $val) {
                if ($key === $dataKey) {
                    if(!is_array($val)){
                       $element->textContent = $val; 
                    }else{
                        $element->textContent = "array";
                    }
                    
                    $element->removeAttribute("data-$level");
                    break;
                }
            }
        }
    }

    /**
     * Process data-template attributes.
     */
    protected function processDataTemplates(): void
    {
        $elements = $this->findAll('[data-template]');

        foreach ($elements as $element) {
            if ($element instanceof \DOMElement) {
                $section = $element->getAttribute('data-template');
            } else {
                throw new FrameworkException("Invalid element type: 'data-template' attribute cannot be accessed.");
            }

            if ($section && isset($this->variables[$section]) && is_array($this->variables[$section])) {
                $items = $this->variables[$section];
                // Get the template content as text
                $templateContent = $element->textContent;

                // Clear the element's content
                $element->textContent = '';

                foreach ($items as $key => $item) {
                    $itemContent = $templateContent;

                    // Create local variables for this iteration
                    $localVars = [
                        'item' => $item,
                        'key' => $key,
                        'index' => $key,
                    ];

                    // Replace variables in the template content
                    foreach ($localVars as $k => $v) {
                        if (is_scalar($v)) {
                            $itemContent = preg_replace('/\{\{\s*'.preg_quote($k, '/').'\s*\}\}/i', (string) $v, $itemContent);
                        }

                        if (is_array($v) || is_object($v)) {
                            $itemContent = $this->replaceComplexVariables($k, $v, $itemContent);
                        }
                    }

                    // Append the processed content
                    $fragment = $this->createFragment($itemContent);
                    $element->appendChild($fragment);
                }
            }

            // Remove the data-template attribute
            $element->removeAttribute('data-template');
            $element->removeAttribute('data-var');
        }
    }

    /**
     * Process data-view attributes.
     */
    protected function processDataViews(): void
    {
        $elements = $this->findAll('[data-view]');

        foreach ($elements as $element) {
            if ($element instanceof \DOMElement) {
                $viewName = $element->getAttribute('data-view');
            } else {
                throw new FrameworkException("Invalid element type: 'data-view' attribute cannot be accessed.");
            }
            $condition = $element->getAttribute('data-if') ?? null;

            // Process conditional display
            if ($condition) {
                $display = $this->evaluateCondition($condition);

                if (!$display) {
                    // Remove the element if condition is false
                    $element->parentNode->removeChild($element);
                    continue;
                }
            }

            try {
                $viewContent = $this->loadTemplate($viewName);

                // Create a document fragment
                $fragment = $this->createFragment($viewContent);

                // Replace the element's content with the fragment
                $element->textContent = '';
                $element->appendChild($fragment);
            } catch (FrameworkException $e) {
                // If view not found, add an error comment
                $comment = $this->createComment("View Error: {$e->getMessage()}");
                $element->textContent = '';
                $element->appendChild($comment);
            }

            // Remove the data-view attribute
            $element->removeAttribute('data-view');
            $element->removeAttribute('data-if');
        }
    }

    /**
     * Process data-model attributes.
     */
    protected function processDataModels(): void
    {
        $elements = $this->findAll('[data-model]');

        foreach ($elements as $element) {
            if ($element instanceof \DOMElement) {
                $modelName = $element->getAttribute('data-model');
            } else {
                throw new FrameworkException("Invalid element type: 'data-model' attribute cannot be accessed.");
            }

            if (isset($this->variables[$modelName])) {
                $modelData = $this->variables[$modelName];

                if (is_array($modelData) || is_object($modelData)) {
                    // For each property in the model
                    foreach ($modelData as $key => $value) {
                        // Find elements with data-bind that match this property
                        $boundElements = $this->findAll("[data-bind=\"{$modelName}.{$key}\"]");

                        foreach ($boundElements as $boundElement) {
                            // Determine how to set the value based on the element type
                            if ($boundElement instanceof \DOMElement) {
                                $tagName = strtolower($boundElement->tagName);
                            } else {
                                throw new FrameworkException("Invalid element type: 'data-bind' attribute cannot be accessed.");
                            }

                            if ($tagName === 'input' || $tagName === 'textarea' || $tagName === 'select') {
                                $boundElement->setAttribute('value', (string) $value);
                            } else {
                                $boundElement->textContent = (string) $value;
                            }

                            // Remove the data-bind attribute
                            $boundElement->removeAttribute('data-bind');
                        }
                    }
                }
            }

            // Remove the data-model attribute
            $element->removeAttribute('data-model');
        }
    }

    /**
     * Process variable placeholders in the template.
     */
    protected function processVariables(string $content): string
    {
        // Replace simple variables {{ var }}
        $content = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function ($matches) {
            $key = $matches[1];

            if (strpos($key, '.') !== false) {
                // Handle nested variables
                return $this->getNestedVariable($key);
            }

            return $this->variables[$key] ?? '';
        }, $content);

        return $content;
    }

    /**
     * Get a nested variable value.
     */
    protected function getNestedVariable(string $key): string
    {
        $parts = explode('.', $key);
        $current = $this->variables;

        foreach ($parts as $part) {
            if (is_array($current) && isset($current[$part])) {
                $current = $current[$part];
            } elseif (is_object($current) && isset($current->$part)) {
                $current = $current->$part;
            } else {
                return '';
            }
        }

        return is_scalar($current) ? (string) $current : '';
    }

    /**
     * Replace complex (nested) variables in a string.
     */
    protected function replaceComplexVariables(string $prefix, $value, string $content): string
    {
        if (is_array($value) || is_object($value)) {
            foreach ($value as $key => $val) {
                $pattern = '/\{\{\s*'.preg_quote($prefix, '/').'\.'.preg_quote($key, '/').'\s*\}\}/i';

                if (is_scalar($val)) {
                    $content = preg_replace($pattern, (string) $val, $content);
                } elseif (is_array($val) || is_object($val)) {
                    $nestedPrefix = $prefix.'.'.$key;
                    $content = $this->replaceComplexVariables($nestedPrefix, $val, $content);
                }
            }
        }

        return $content;
    }

    /**
     * Evaluate a template condition.
     */
    protected function evaluateCondition(string $condition): bool
    {
        // Simple condition evaluation
        if (isset($this->variables[$condition])) {
            return (bool) $this->variables[$condition];
        }

        // Handle negation: !variable
        if (strpos($condition, '!') === 0) {
            $varName = substr($condition, 1);
            if (isset($this->variables[$varName])) {
                return !(bool) $this->variables[$varName];
            }
        }

        // Handle equality: var==value
        if (strpos($condition, '==') !== false) {
            list($left, $right) = explode('==', $condition, 2);
            $left = trim($left);
            $right = trim($right);

            $leftValue = isset($this->variables[$left]) ? $this->variables[$left] : $left;
            $rightValue = isset($this->variables[$right]) ? $this->variables[$right] : $right;

            return $leftValue == $rightValue;
        }

        // Handle inequality: var!=value
        if (strpos($condition, '!=') !== false) {
            list($left, $right) = explode('!=', $condition, 2);
            $left = trim($left);
            $right = trim($right);

            $leftValue = isset($this->variables[$left]) ? $this->variables[$left] : $left;
            $rightValue = isset($this->variables[$right]) ? $this->variables[$right] : $right;

            return $leftValue != $rightValue;
        }

        return false;
    }

    /**
     * Get all assigned variables.
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Clear all assigned variables.
     *
     * @return $this
     */
    public function clearVariables(): self
    {
        $this->variables = [];

        return $this;
    }

    /**
     * Clear the template cache.
     *
     * @return $this
     */
    public function clearCache(): self
    {
        $this->cache = [];

        return $this;
    }

    public function getXpath(): \DOMXPath
    {
        return $this->xpath;
    }
}