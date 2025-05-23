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
     * The database connection instance.
     * The $queries array is used to store the queries that are executed
     * during the lifecycle of the model. This can be useful for debugging
     * or logging purposes.
     */
    protected DatabaseConnection $db;
    protected array $queries = [];
    /**
     * Load metadata from the configuration file.
     */
    public function __construct(DatabaseConnection $db)
    {
        // echo 'Executing TemplateModel constructor<br>'.PHP_EOL;
        parent::__construct();
        $this->app = app();
        $this->setDB($db);
        $this->container = $this->app->getContainer();
        $baseTemplate = $this->container->make('config')['template']['baseTemplate'] ?? 'layouts/base';
        //$baseTemplate = $fileLoader->findFile($baseTemplate, null, 'php');
        // echo 'Loading metadata from config<br>'.PHP_EOL;
        $baseUri = $this->container->make('config')['app']['baseUri'];
        $this->setAttribute('baseTemplate', $baseTemplate);
        $this->setAttribute('templateDir', $this->app->templatesPath());
        $this->setAttribute('baseUri', $baseUri);
        $this->loadAssets($this->db);
        $this->loadLinks($this->db); 
        //debug($this->getAttribute("links"));
    }
    protected function setDB(DatabaseConnection $db): void
    {
        $this->db = $db;
    }
    public function addQuery(string $key, string $query): void
    {
        $this->queries[$key] = $query;
    }


    /**
     * Fetch assets from the database.
     */
    public function loadAssets(): void
    {
        $query = 'SELECT filePath FROM assets ORDER BY `priority` DESC';
        $this->setAttribute('assets', $this->db->select($query));
    }

    /**
     * Fetch navigation links from the database.
     */
    public function loadLinks(): void
    {
        $query = 'SELECT uri, icon, label, type FROM nav_links WHERE enabled = 1 ORDER BY type, id';
        $this->setAttribute('links', $this->db->select($query));
    }

    /**
     * Get the slogan or logo from the attributes.
     */
    public function getTemplateData(string $key)
    {
        return $this->getAttribute($key);
    }
}