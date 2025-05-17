<?php

namespace App\Models;

use Framework\Database\DatabaseConnection;
use Framework\Model\BaseModel;

/**
 * Template Model.
 *
 * Handles template-related data such as metadata, assets, and links.
 */
class TemplateModel extends BaseModel
{
    /**
     * Load metadata from the configuration file.
     */
    public function __construct(protected string $config = 'template.php')
    {
        // echo 'Executing TemplateModel constructor<br>'.PHP_EOL;
        global $app;
        $container = $app->getContainer();
        parent::__construct();

        $this->loadMetadata();
        $this->loadAssets($container->make('db'));
        $this->loadLinks($container->make('db'));
    }

    public function loadMetadata(): void
    {
        // echo 'Loading metadata from config<br>'.PHP_EOL;
        global $app;
        $container = $app->getContainer();
        $fileLoader = $container->make('fileLoader');

        $configData = $fileLoader->includeFile($this->config);
        // var_dump(value: $configData);
        $this->fill($configData);
    }

    /**
     * Fetch assets from the database.
     */
    public function loadAssets(DatabaseConnection $db): void
    {
        $query = 'SELECT filePath FROM assets ORDER BY `priority` DESC';
        $this->setAttribute('assets', $db->select($query));
    }

    /**
     * Fetch navigation links from the database.
     */
    public function loadLinks(DatabaseConnection $db): void
    {
        $query = 'SELECT uri, icon, label FROM nav_links WHERE enabled = 1 ORDER BY type, id';
        $this->setAttribute('links', $db->select($query));
    }

    /**
     * Get the slogan or logo from the attributes.
     */
    public function getTemplateData(string $key)
    {
        return $this->getAttribute($key);
    }
}