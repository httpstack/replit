<?php

namespace Framework\FileSystem;

use Framework\Exceptions\FrameworkException;

/**
 * File Loader.
 *
 * Handles file loading, mapping directories, and finding files
 */
class FileLoader
{
    /**
     * Mapped directories.
     */
    protected array $mappedDirectories = [];

    /**
     * Default extension for files.
     */
    protected string $defaultExtension = 'php';

    /**
     * File cache.
     */
    protected array $fileCache = [];

    /**
     * Create a new file loader.
     */
    public function __construct()
    {
        // Constructor
    }

    /**
     * Map a directory to a namespace.
     *
     * @return $this
     */
    public function mapDirectory(string $name, string $directory): self
    {
        // Ensure the directory exists
        if (!is_dir($directory)) {
            throw new FrameworkException("Directory not found: {$directory}");
        }

        $this->mappedDirectories[$name] = rtrim($directory, '/');

        return $this;
    }

    /**
     * Get a mapped directory path.
     */
    public function getDirectory(string $name): ?string
    {
        return $this->mappedDirectories[$name] ?? null;
    }

    /**
     * Get all mapped directories.
     */
    public function getDirectories(): array
    {
        return $this->mappedDirectories;
    }

    /**
     * Find a file by name in mapped directories (searches subdirectories).
     */
    public function findFile(string $name, ?string $directory = null, ?string $extension = null): ?string
    {
        $extension = $extension ?? $this->defaultExtension;

        // Add extension if not already present
        if (!empty($extension) && !preg_match('/\.'.preg_quote($extension, '/').'$/i', $name)) {
            $name .= '.'.$extension;
        }

        // Look in a specific directory if provided
        if ($directory !== null) {
            $dir = $this->mappedDirectories[$directory] ?? $directory;

            if (is_dir($dir)) {
                $files = $this->scanDirectoryForExtension($dir, $extension);
                foreach ($files as $file) {
                    if (basename($file) === $name) {
                        return $file;
                    }
                }
            }

            return null;
        }

        // Look in all mapped directories
        foreach ($this->mappedDirectories as $dir) {
            $files = $this->scanDirectoryForExtension($dir, $extension);
            foreach ($files as $file) {
                if (basename($file) === $name) {
                    return $file;
                }
            }
        }

        return null;
    }

    /**
     * Find all files by extension in mapped directories.
     */
    public function findFilesByExtension(string $extension, ?string $directory = null): array
    {
        $files = [];

        // Look in a specific directory if provided
        if ($directory !== null) {
            $dir = $this->mappedDirectories[$directory] ?? $directory;

            if (is_dir($dir)) {
                $files = $this->scanDirectoryForExtension($dir, $extension);
            }

            return $files;
        }

        // Look in all mapped directories
        foreach ($this->mappedDirectories as $dir) {
            $dirFiles = $this->scanDirectoryForExtension($dir, $extension);
            $files = array_merge($files, $dirFiles);
        }

        return $files;
    }

    /**
     * Scan a directory for files with a specific extension.
     */
    protected function scanDirectoryForExtension(string $directory, string $extension): array
    {
        $files = [];
        $extension = ltrim($extension, '.');

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $directory,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
                )
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === $extension) {
                    $files[] = $file->getPathname();
                }
            }
        } catch (\Exception $e) {
            // Directory may not exist or be accessible
        }

        return $files;
    }

    /**
     * Load a file's contents.
     *
     * @throws FrameworkException
     */
    public function loadFile(string $path, bool $useCache = true): string
    {
        // Check the cache first
        if ($useCache && isset($this->fileCache[$path])) {
            return $this->fileCache[$path];
        }

        // Resolve the path if it's a mapped directory
        if (isset($this->mappedDirectories[$path])) {
            $path = $this->mappedDirectories[$path];
        }

        // If the path is not absolute, try to find the file
        if (!is_file($path)) {
            $foundPath = $this->findFile($path);

            if ($foundPath === null) {
                throw new FrameworkException("File not found: {$path}");
            }

            $path = $foundPath;
        }

        // Load the file
        $content = include $path;

        if ($content === false) {
            throw new FrameworkException("Failed to read file: {$path}");
        }

        // Cache the content
        if ($useCache) {
            $this->fileCache[$path] = $content;
        }

        return $content;
    }

    /**
     * Require a PHP file.
     *
     * @throws FrameworkException
     */
    public function requireFile(string $path)
    {
        // Resolve the path if it's a mapped directory
        if (isset($this->mappedDirectories[$path])) {
            $path = $this->mappedDirectories[$path];
        }

        // If the path is not absolute, try to find the file
        if (!is_file($path)) {
            $foundPath = $this->findFile($path);

            if ($foundPath === null) {
                throw new FrameworkException("File not found: {$path}");
            }

            $path = $foundPath;
        }

        return require $path;
    }
    public function parseJsonFile(string $path): array
    {
        if(is_file($path)){
            $content = file_get_contents($path);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new FrameworkException("Failed to parse JSON file: {$path}");
            }

            return $data;
        } else {
            throw new FrameworkException("File not found: {$path}");
        }
    }
    /**
     * Include a PHP file.
     *
     * @throws FrameworkException
     */
    public function includeFile(string $path)
    {
        // Resolve the path if it's a mapped directory
        if (isset($this->mappedDirectories[$path])) {
            $path = $this->mappedDirectories[$path];
        }

        // If the path is not absolute, try to find the file
        if (!is_file($path)) {
            $foundPath = $this->findFile($path);

            if ($foundPath === null) {
                throw new FrameworkException("File not found: {$path}");
            }

            $path = $foundPath;
        }

        return include $path;
    }

    /**
     * Write content to a file.
     */
    public function writeFile(string $path, string $content): bool
    {
        // Ensure the directory exists
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write the content
        $result = file_put_contents($path, $content);

        // Update the cache
        if ($result !== false) {
            $this->fileCache[$path] = $content;

            return true;
        }

        return false;
    }

    /**
     * Set the default file extension.
     *
     * @return $this
     */
    public function setDefaultExtension(string $extension): self
    {
        $this->defaultExtension = ltrim($extension, '.');

        return $this;
    }

    /**
     * Get the default file extension.
     */
    public function getDefaultExtension(): string
    {
        return $this->defaultExtension;
    }

    /**
     * Clear the file cache.
     *
     * @return $this
     */
    public function clearCache(): self
    {
        $this->fileCache = [];

        return $this;
    }

    /**
     * Handle duplicate files.
     */
    public function handleDuplicates(array $files, string $strategy = 'first'): array
    {
        $result = [];
        $fileNames = [];

        foreach ($files as $file) {
            $name = basename($file);

            if (!isset($fileNames[$name])) {
                $fileNames[$name] = [];
            }

            $fileNames[$name][] = $file;
        }

        foreach ($fileNames as $name => $paths) {
            if (count($paths) === 1) {
                $result[] = $paths[0];
            } else {
                if ($strategy === 'first') {
                    $result[] = $paths[0];
                } elseif ($strategy === 'last') {
                    $result[] = end($paths);
                } elseif ($strategy === 'all') {
                    $result = array_merge($result, $paths);
                }
            }
        }

        return $result;
    }
}