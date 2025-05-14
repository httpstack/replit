<?php

namespace App\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;

class TemplateMiddleware
{
    public function __invoke(Request $request, callable $next): Response
    {
        // Get the response from the next middleware or controller
        $response = $next($request);

        // Inject the template into the response body
        $body = $response->getBody();
        $template = $this->loadTemplate($body);

        $response->setBody($template);

        return $response;
    }

    protected function loadTemplate(string $content): string
    {
        // Example: Wrap the content in a basic HTML template
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Application</title>
</head>
<body>
    $content
</body>
</html>
HTML;
    }
}