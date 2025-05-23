<?php

echo '<pre>';
var_dump((array) $this->templateModel->getAttributes()[0]);
echo '</pre>';

    public function loadMetadata(string|array $config): void
    {   
        global $app;
        // echo 'Loading metadata from config<br>'.PHP_EOL;
        if (is_string($config)) {
            $container = $app->getContainer();
            $fileLoader = $container->make('fileLoader');
            $config = $fileLoader->includeFile($config);
        } 
        // echo 'Loading metadata from config<br>'.PHP_EOL;
        // var_dump(value: $configData);
        $this->fill($config);
    }
    config($this->container, 'template.baseTemplate', 'layouts/base');