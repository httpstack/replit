<?php

namespace Framework\Template;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Framework\Exceptions\FrameworkException;

/**
 * DOM Manipulator
 * 
 * Extends DOMDocument for advanced template manipulation
 */
class DomManipulator extends DOMDocument
{
    /**
     * XPath instance
     * 
     * @var DOMXPath|null
     */
    protected ?DOMXPath $xpath = null;
    
    /**
     * Create a new DOM manipulator
     * 
     * @param string $version
     * @param string $encoding
     */
    public function __construct(string $version = '1.0', string $encoding = 'UTF-8')
    {
        parent::__construct($version, $encoding);
        
        // Configure DOMDocument to handle HTML5 and special characters
        $this->registerNodeClass('DOMElement', DOMElement::class);
        $this->formatOutput = true;
        $this->preserveWhiteSpace = false;
    }
    
    /**
     * Load HTML content into the document
     * 
     * @param string $html
     * @return bool
     */
    public function loadHTML($html, $options = 0): bool
    {
        // Reset XPath if we're loading new content
        $this->xpath = null;
        
        // Use libxml options to suppress errors and handle HTML5
        $options = $options | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING;
        
        // Fix encoding issues by wrapping in meta tag if not present
        if (strpos($html, '<meta charset') === false && strpos($html, '<meta http-equiv="Content-Type"') === false) {
            $html = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . $html;
        }
        
        // Load the HTML content
        $result = parent::loadHTML($html, $options);
        
        // Create a new XPath instance for querying
        $this->xpath = new DOMXPath($this);
        
        return $result;
    }
    
    /**
     * Save the document as HTML
     * 
     * @return string
     */
    public function saveHTML($node = null): string
    {
        if ($node === null) {
            $html = parent::saveHTML();
            
            // Remove doctype and meta tags if present
            $html = preg_replace('/<\!DOCTYPE.*?>/i', '', $html);
            $html = preg_replace('/<meta[^>]*>/i', '', $html);
            
            return trim($html);
        }
        
        return parent::saveHTML($node);
    }
    
    /**
     * Find an element by CSS selector
     * 
     * @param string $selector
     * @return DOMElement|null
     */
    public function find(string $selector): ?DOMElement
    {
        $elements = $this->findAll($selector);
        
        return $elements->length > 0 ? $elements->item(0) : null;
    }
    
    /**
     * Find all elements matching a CSS selector
     * 
     * @param string $selector
     * @return DOMNodeList
     * @throws FrameworkException
     */
    public function findAll(string $selector): DOMNodeList
    {
        if (!$this->xpath) {
            throw new FrameworkException("No document loaded for query");
        }
        
        // Convert CSS selector to XPath
        $xpath = $this->cssToXPath($selector);
        
        // Execute the XPath query
        return $this->xpath->query($xpath);
    }
    
    /**
     * Convert a CSS selector to XPath
     * 
     * @param string $selector
     * @return string
     */
    protected function cssToXPath(string $selector): string
    {
        // Handle ID selectors
        if ($selector[0] === '#') {
            return "//*[@id='" . substr($selector, 1) . "']";
        }
        
        // Handle class selectors
        if ($selector[0] === '.') {
            return "//*[contains(concat(' ', normalize-space(@class), ' '), ' " . substr($selector, 1) . " ')]";
        }
        
        // Handle attribute selectors
        if (strpos($selector, '[') !== false && strpos($selector, ']') !== false) {
            preg_match('/\[(.*?)\]/', $selector, $matches);
            $attr = $matches[1];
            
            // Extract the tag name if present
            $tag = '*';
            if (preg_match('/^([a-zA-Z0-9]+)\[/', $selector, $tagMatches)) {
                $tag = $tagMatches[1];
            }
            
            // Handle data attributes
            if (strpos($attr, 'data-') === 0) {
                return "//{$tag}[@{$attr}]";
            }
            
            // Handle attribute=value
            if (strpos($attr, '=') !== false) {
                list($name, $value) = explode('=', $attr, 2);
                // Remove quotes if present
                $value = trim($value, '"\'');
                return "//{$tag}[@{$name}='{$value}']";
            }
            
            return "//{$tag}[@{$attr}]";
        }
        
        // Handle tag selectors
        return "//{$selector}";
    }
    
    /**
     * Create a document fragment from HTML
     * 
     * @param string $html
     * @return \DOMDocumentFragment
     */
    public function createFragment(string $html): \DOMDocumentFragment
    {
        $fragment = $this->createDocumentFragment();
        
        // Create a temporary document to parse the HTML
        $tempDoc = new DOMDocument();
        $tempDoc->loadHTML('<div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        
        // Import the body content into our fragment
        $body = $tempDoc->getElementsByTagName('div')->item(0);
        
        foreach ($body->childNodes as $node) {
            $importedNode = $this->importNode($node, true);
            $fragment->appendChild($importedNode);
        }
        
        return $fragment;
    }
    
    /**
     * Create a new element
     * 
     * @param string $name
     * @param string $value
     * @return DOMElement
     */
    public function createElement($name, $value = null): DOMElement
    {
        $element = parent::createElement($name);
        
        if ($value !== null) {
            $text = $this->createTextNode($value);
            $element->appendChild($text);
        }
        
        return $element;
    }
    
    /**
     * Add a class to an element
     * 
     * @param DOMElement $element
     * @param string $className
     * @return DOMElement
     */
    public function addClass(DOMElement $element, string $className): DOMElement
    {
        $classes = $element->getAttribute('class');
        $classArray = $classes ? explode(' ', $classes) : [];
        
        if (!in_array($className, $classArray)) {
            $classArray[] = $className;
            $element->setAttribute('class', implode(' ', $classArray));
        }
        
        return $element;
    }
    
    /**
     * Remove a class from an element
     * 
     * @param DOMElement $element
     * @param string $className
     * @return DOMElement
     */
    public function removeClass(DOMElement $element, string $className): DOMElement
    {
        $classes = $element->getAttribute('class');
        $classArray = $classes ? explode(' ', $classes) : [];
        
        if (in_array($className, $classArray)) {
            $classArray = array_diff($classArray, [$className]);
            $element->setAttribute('class', implode(' ', $classArray));
        }
        
        return $element;
    }
    
    /**
     * Check if an element has a class
     * 
     * @param DOMElement $element
     * @param string $className
     * @return bool
     */
    public function hasClass(DOMElement $element, string $className): bool
    {
        $classes = $element->getAttribute('class');
        $classArray = $classes ? explode(' ', $classes) : [];
        
        return in_array($className, $classArray);
    }
    
    /**
     * Set the HTML content of an element
     * 
     * @param DOMElement $element
     * @param string $html
     * @return DOMElement
     */
    public function setHtml(DOMElement $element, string $html): DOMElement
    {
        // Remove all child nodes
        while ($element->firstChild) {
            $element->removeChild($element->firstChild);
        }
        
        // Add the new content
        $fragment = $this->createFragment($html);
        $element->appendChild($fragment);
        
        return $element;
    }
    
    /**
     * Set the text content of an element
     * 
     * @param DOMElement $element
     * @param string $text
     * @return DOMElement
     */
    public function setText(DOMElement $element, string $text): DOMElement
    {
        // Remove all child nodes
        while ($element->firstChild) {
            $element->removeChild($element->firstChild);
        }
        
        // Add the new text content
        $textNode = $this->createTextNode($text);
        $element->appendChild($textNode);
        
        return $element;
    }
    
    /**
     * Remove an element from the document
     * 
     * @param DOMElement $element
     * @return bool
     */
    public function removeElement(DOMElement $element): bool
    {
        if ($element->parentNode) {
            $element->parentNode->removeChild($element);
            return true;
        }
        
        return false;
    }
    
    /**
     * Replace an element with another element or HTML
     * 
     * @param DOMElement $element
     * @param DOMElement|string $replacement
     * @return bool
     */
    public function replaceElement(DOMElement $element, $replacement): bool
    {
        if (!$element->parentNode) {
            return false;
        }
        
        if (is_string($replacement)) {
            $fragment = $this->createFragment($replacement);
            $element->parentNode->replaceChild($fragment, $element);
        } else {
            $element->parentNode->replaceChild($replacement, $element);
        }
        
        return true;
    }
    
    /**
     * Insert HTML before an element
     * 
     * @param DOMElement $element
     * @param string $html
     * @return bool
     */
    public function insertHtmlBefore(DOMElement $element, string $html): bool
    {
        if (!$element->parentNode) {
            return false;
        }
        
        $fragment = $this->createFragment($html);
        $element->parentNode->insertBefore($fragment, $element);
        
        return true;
    }
    
    /**
     * Insert HTML after an element
     * 
     * @param DOMElement $element
     * @param string $html
     * @return bool
     */
    public function insertHtmlAfter(DOMElement $element, string $html): bool
    {
        if (!$element->parentNode) {
            return false;
        }
        
        $fragment = $this->createFragment($html);
        
        if ($element->nextSibling) {
            $element->parentNode->insertBefore($fragment, $element->nextSibling);
        } else {
            $element->parentNode->appendChild($fragment);
        }
        
        return true;
    }
    
    /**
     * Append HTML to an element
     * 
     * @param DOMElement $element
     * @param string $html
     * @return DOMElement
     */
    public function appendHtml(DOMElement $element, string $html): DOMElement
    {
        $fragment = $this->createFragment($html);
        $element->appendChild($fragment);
        
        return $element;
    }
    
    /**
     * Prepend HTML to an element
     * 
     * @param DOMElement $element
     * @param string $html
     * @return DOMElement
     */
    public function prependHtml(DOMElement $element, string $html): DOMElement
    {
        $fragment = $this->createFragment($html);
        
        if ($element->hasChildNodes()) {
            $element->insertBefore($fragment, $element->firstChild);
        } else {
            $element->appendChild($fragment);
        }
        
        return $element;
    }
}
