<?php

namespace Framework\FileSystem;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use SplFileInfo;
use Framework\Exceptions\FrameworkException;

/**
 * Directory Mapper
 * 
 * Provides tools for working with directories and file paths
 */
class DirectoryMapper
{
    /**
     * Map of directory paths
     * 
     * @var array
     */
    protected array $directories = [];
    
    /**
     * Create a new directory mapper
     * 
     * @param array $directories
     */
    public function __construct(array $directories = [])
    {
        foreach ($directories as $name => $path) {
            $this->addDirectory($name, $path);
        }
    }
    
    /**
     * Add a directory to the map
     * 
     * @param string $name
     * @param string $path
     * @return $this
     * @throws FrameworkException
     */
    public function addDirectory(string $name, string $path): self
    {
        // Normalize the path
        $path = rtrim($path, '/\\');
        
        // Ensure the directory exists
        if (!is_dir($path)) {
            throw new FrameworkException("Directory not found: {$path}");
        }
        
        $this->directories[$name] = $path;
        
        return $this;
    }
    
    /**
     * Get a directory path by name
     * 
     * @param string $name
     * @return string|null
     */
    public function getDirectory(string $name): ?string
    {
        return $this->directories[$name] ?? null;
    }
    
    /**
     * Check if a directory exists in the map
     * 
     * @param string $name
     * @return bool
     */
    public function hasDirectory(string $name): bool
    {
        return isset($this->directories[$name]);
    }
    
    /**
     * Get all mapped directories
     * 
     * @return array
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }
    
    /**
     * Remove a directory from the map
     * 
     * @param string $name
     * @return $this
     */
    public function removeDirectory(string $name): self
    {
        unset($this->directories[$name]);
        return $this;
    }
    
    /**
     * Get all files in a directory
     * 
     * @param string $directory
     * @param string|null $pattern
     * @param bool $recursive
     * @return array
     * @throws FrameworkException
     */
    public function getFiles(string $directory, ?string $pattern = null, bool $recursive = false): array
    {
        $path = $this->resolvePath($directory);
        
        if (!is_dir($path)) {
            throw new FrameworkException("Directory not found: {$path}");
        }
        
        $files = [];
        
        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path, 
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
                )
            );
        } else {
            $iterator = new \DirectoryIterator($path);
        }
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = $file->getPathname();
                
                // If a pattern is provided, check if the file matches
                if ($pattern !== null && !fnmatch($pattern, $file->getFilename())) {
                    continue;
                }
                
                $files[] = $filePath;
            }
        }
        
        return $files;
    }
    
    /**
     * Get all subdirectories in a directory
     * 
     * @param string $directory
     * @param bool $recursive
     * @return array
     * @throws FrameworkException
     */
    public function getSubdirectories(string $directory, bool $recursive = false): array
    {
        $path = $this->resolvePath($directory);
        
        if (!is_dir($path)) {
            throw new FrameworkException("Directory not found: {$path}");
        }
        
        $directories = [];
        
        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path, 
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
                ),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            $iterator = new \DirectoryIterator($path);
        }
        
        foreach ($iterator as $item) {
            if ($item->isDir() && !$item->isDot()) {
                $directories[] = $item->getPathname();
            }
        }
        
        return $directories;
    }
    
    /**
     * Find files by extension
     * 
     * @param string $directory
     * @param string $extension
     * @param bool $recursive
     * @return array
     * @throws FrameworkException
     */
    public function findByExtension(string $directory, string $extension, bool $recursive = false): array
    {
        $extension = ltrim($extension, '.');
        $path = $this->resolvePath($directory);
        
        if (!is_dir($path)) {
            throw new FrameworkException("Directory not found: {$path}");
        }
        
        $files = [];
        
        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path, 
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
                )
            );
        } else {
            $iterator = new \DirectoryIterator($path);
        }
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === $extension) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Find files by name
     * 
     * @param string $directory
     * @param string $name
     * @param bool $recursive
     * @return array
     * @throws FrameworkException
     */
    public function findByName(string $directory, string $name, bool $recursive = false): array
    {
        $path = $this->resolvePath($directory);
        
        if (!is_dir($path)) {
            throw new FrameworkException("Directory not found: {$path}");
        }
        
        $files = [];
        
        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path, 
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
                )
            );
        } else {
            $iterator = new \DirectoryIterator($path);
        }
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $name) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Create a directory if it doesn't exist
     * 
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    public function createDirectory(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }
        
        return mkdir($path, $mode, $recursive);
    }
    
    /**
     * Delete a directory and its contents
     * 
     * @param string $directory
     * @return bool
     * @throws FrameworkException
     */
    public function deleteDirectory(string $directory): bool
    {
        $path = $this->resolvePath($directory);
        
        if (!is_dir($path)) {
            throw new FrameworkException("Directory not found: {$path}");
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path, 
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        
        return rmdir($path);
    }
    
    /**
     * Copy a directory and its contents
     * 
     * @param string $source
     * @param string $destination
     * @param int $mode
     * @return bool
     * @throws FrameworkException
     */
    public function copyDirectory(string $source, string $destination, int $mode = 0755): bool
    {
        $sourcePath = $this->resolvePath($source);
        
        if (!is_dir($sourcePath)) {
            throw new FrameworkException("Source directory not found: {$sourcePath}");
        }
        
        // Create the destination directory if it doesn't exist
        if (!is_dir($destination)) {
            $this->createDirectory($destination, $mode);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $sourcePath, 
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($sourcePath));
            $targetPath = $destination . $relativePath;
            
            if ($item->isDir()) {
                $this->createDirectory($targetPath, $mode);
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
        
        return true;
    }
    
    /**
     * Resolve a path, checking if it's a mapped directory
     * 
     * @param string $path
     * @return string
     */
    protected function resolvePath(string $path): string
    {
        if (isset($this->directories[$path])) {
            return $this->directories[$path];
        }
        
        return $path;
    }
    
    /**
     * Get metadata about a file or directory
     * 
     * @param string $path
     * @return array
     */
    public function getMetadata(string $path): array
    {
        $path = $this->resolvePath($path);
        
        if (!file_exists($path)) {
            return [];
        }
        
        $info = new SplFileInfo($path);
        
        return [
            'path' => $path,
            'filename' => $info->getFilename(),
            'extension' => $info->getExtension(),
            'size' => $info->getSize(),
            'type' => $info->getType(),
            'is_file' => $info->isFile(),
            'is_dir' => $info->isDir(),
            'permissions' => $info->getPerms(),
            'owner' => $info->getOwner(),
            'group' => $info->getGroup(),
            'last_modified' => $info->getMTime(),
            'last_accessed' => $info->getATime(),
        ];
    }
}
