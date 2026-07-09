<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use ReflectionClass;

class SyncPostmanCollection extends Command
{
    protected $signature = 'postman:sync';
    protected $description = 'Sync API routes to Postman collection';

    public function handle()
    {
        $apiKey = env('POSTMAN_API_KEY');
        $workspaceId = env('POSTMAN_WORKSPACE_ID');
        $collectionId = env('POSTMAN_COLLECTION_ID');

        if (!$apiKey) {
            $this->error('POSTMAN_API_KEY not set in .env');
            return 1;
        }

        if (!$workspaceId) {
            $this->error('POSTMAN_WORKSPACE_ID not set in .env');
            return 1;
        }

        $collection = $this->buildCollection();

        if ($collectionId) {
            $this->info('Updating existing collection...');
            $response = Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'Content-Type' => 'application/json'
            ])->put("https://api.getpostman.com/collections/{$collectionId}", [
                'collection' => $collection
            ]);

            if ($response->successful()) {
                $this->info('✅ Collection updated successfully!');
                $this->info("View at: https://www.postman.com/collection/{$collectionId}");
                return 0;
            }
        }

        $this->info('Creating new collection in workspace...');
        $response = Http::withHeaders([
            'X-Api-Key' => $apiKey,
            'Content-Type' => 'application/json'
        ])->post("https://api.getpostman.com/collections?workspace={$workspaceId}", [
            'collection' => $collection
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $newId = $data['collection']['uid'];
            $this->info('✅ New collection created successfully!');
            $this->info("Collection ID: {$newId}");
            $this->info("Add this to your .env file:");
            $this->line("POSTMAN_COLLECTION_ID={$newId}");
            $this->info("\nView at: https://www.postman.com/collection/{$newId}");
            return 0;
        }

        $this->error('❌ Failed to sync collection');
        $this->error('Status: ' . $response->status());
        $this->error('Response: ' . $response->body());
        return 1;
    }

    private function buildCollection()
    {
        $routes = Route::getRoutes();
        $grouped = [];

        foreach ($routes as $route) {
            $uri = $route->uri();

            if (!$this->shouldIncludeRoute($uri)) {
                continue;
            }

            $groupPath = $this->getGroupPath($route);
            $methods = array_diff($route->methods(), ['HEAD']);

            foreach ($methods as $method) {
                $this->addToNestedGroup($grouped, $groupPath, $this->buildRequestItem($route, $method));
            }
        }

        $items = $this->convertToPostmanStructure($grouped);

        return [
            'info' => [
                'name' => config('app.name', 'Laravel'),
                'description' => 'Auto-generated API collection - Last updated: ' . now()->toDateTimeString(),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
            ],
            'item' => $items,
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{bearerToken}}',
                        'type' => 'string'
                    ]
                ]
            ],
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => env('APP_URL', 'http://127.0.0.1:8000'),
                    'type' => 'string'
                ],
                [
                    'key' => 'bearerToken',
                    'value' => '',
                    'type' => 'string'
                ]
            ]
        ];
    }

    private function shouldIncludeRoute($uri)
    {
        if (str_starts_with($uri, 'api/')) {
            return true;
        }

        $excludedRoutes = [
            'sanctum/csrf-cookie',
            'storage/{path}',
            '/',
        ];

        return !in_array($uri, $excludedRoutes);
    }

    private function getGroupPath($route)
    {
        $uri = $route->uri();
        $action = $route->getActionName();

        if (str_contains($action, '@')) {
            $controller = explode('@', $action)[0];
            return [str_replace('Controller', '', class_basename($controller))];
        }

        if (str_contains($action, '::')) {
            $controller = explode('::', $action)[0];
            return [str_replace('Controller', '', class_basename($controller))];
        }

        return ['Other'];
    }

    private function addToNestedGroup(&$grouped, $path, $item)
    {
        $current = &$grouped;

        foreach ($path as $segment) {
            if (!isset($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        if (!isset($current['_items'])) {
            $current['_items'] = [];
        }

        $current['_items'][] = $item;
    }

    private function convertToPostmanStructure($grouped)
    {
        $items = [];

        foreach ($grouped as $name => $content) {
            if ($name === '_items') {
                continue;
            }

            $folder = [
                'name' => $name,
                'item' => []
            ];

            if (isset($content['_items'])) {
                $folder['item'] = array_merge($folder['item'], $content['_items']);
            }

            foreach ($content as $key => $subContent) {
                if ($key !== '_items') {
                    $folder['item'][] = $this->convertToPostmanFolder($key, $subContent);
                }
            }

            $items[] = $folder;
        }

        return $items;
    }

    private function convertToPostmanFolder($name, $content)
    {
        $folder = [
            'name' => $name,
            'item' => []
        ];

        if (isset($content['_items'])) {
            $folder['item'] = array_merge($folder['item'], $content['_items']);
        }

        foreach ($content as $key => $subContent) {
            if ($key !== '_items') {
                $folder['item'][] = $this->convertToPostmanFolder($key, $subContent);
            }
        }

        return $folder;
    }

    private function buildRequestItem($route, $method)
    {
        $uri = $route->uri();
        $action = $route->getActionName();
        $controllerName = null;
        $methodName = null;

        if (str_contains($action, '@')) {
            [$controller, $methodName] = explode('@', $action);
            $controllerName = str_replace('Controller', '', class_basename($controller));
        }

        $friendlyName = $this->getFriendlyTitle($methodName, $controllerName);

        $item = [
            'name' => $friendlyName,
            'request' => [
                'method' => strtoupper($method),
                'header' => [
                    [
                        'key' => 'Accept',
                        'value' => 'application/json',
                        'type' => 'text'
                    ],
                    [
                        'key' => 'Content-Type',
                        'value' => 'application/json',
                        'type' => 'text'
                    ]
                ],
                'url' => [
                    'raw' => '{{base_url}}/' . $uri,
                    'host' => ['{{base_url}}'],
                    'path' => array_filter(explode('/', $uri))
                ],
                'description' => $this->getRouteDescription($route, $method, $friendlyName)
            ]
        ];

        // Add post-request script for login endpoints
        if ($this->isLoginRoute($uri, $methodName)) {
            $item['event'] = [
                [
                    'listen' => 'test',
                    'script' => [
                        'exec' => [
                            'const response = pm.response.json();',
                            'if (response.accessToken) {',
                            '    pm.collectionVariables.set("bearerToken", response.accessToken);',
                            '}'
                        ],
                        'type' => 'text/javascript'
                    ]
                ]
            ];
        }

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $exampleBody = $this->getExampleBodyFromFormRequest($route);

            $item['request']['body'] = [
                'mode' => 'raw',
                'raw' => json_encode($exampleBody, JSON_PRETTY_PRINT),
                'options' => [
                    'raw' => [
                        'language' => 'json'
                    ]
                ]
            ];
        }

        return $item;
    }

    private function isLoginRoute($uri, $methodName)
    {
        return str_contains($uri, 'login') ||
            strtolower($methodName ?? '') === 'login';
    }

    private function getFriendlyTitle($methodName, $controller = null)
    {
        $map = [
            'index'   => 'List',
            'show'    => 'Get',
            'store'   => 'Create',
            'update'  => 'Update',
            'destroy' => 'Delete',
            'edit'    => 'Edit',
            'create'  => 'Create Form',
        ];

        $friendly = $map[strtolower($methodName ?? '')] ?? ucfirst($methodName ?? 'Request');

        if ($controller && strtolower($controller) !== 'auth') {
            $friendly .= ' ' . $controller;
        }

        return trim($friendly);
    }

    private function getCustomOtherDescription($uri)
    {
        $map = [
            'api/health' => 'Health check endpoint to verify the API is running.',
        ];

        return $map[$uri] ?? 'Miscellaneous endpoint without a dedicated controller.';
    }

    private function getRouteDescription($route, $method, $friendlyName)
    {
        $uri = $route->uri();
        $group = $this->getGroupPath($route)[0];

        $description = "**Endpoint:** `{$method} /{$uri}`\n\n";

        if ($group === 'Other') {
            $description .= "**Description:** " . $this->getCustomOtherDescription($uri);
        } else {
            $description .= "**Description:** {$friendlyName}\n\n" .
                "**Parameters:** " . json_encode($route->parameterNames());
        }

        return $description;
    }

    private function getExampleBodyFromFormRequest($route)
    {
        $action = $route->getActionName();

        if (!str_contains($action, '@') && !str_contains($action, '::')) {
            return $this->getDefaultExampleBody();
        }

        if (str_contains($action, '@')) {
            [$controllerClass, $method] = explode('@', $action);
        } else {
            [$controllerClass, $method] = explode('::', $action);
        }

        if (!class_exists($controllerClass)) {
            return $this->getDefaultExampleBody();
        }

        try {
            $reflector = new ReflectionClass($controllerClass);
            $controllerMethod = $reflector->getMethod($method);
            $parameters = $controllerMethod->getParameters();

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                if ($type && !$type->isBuiltin()) {
                    $className = $type->getName();
                    if (class_exists($className) && is_subclass_of($className, 'Illuminate\Foundation\Http\FormRequest')) {
                        $this->info("Found FormRequest: {$className}");
                        return $this->generateExampleFromFormRequest($className);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warn("Error inspecting controller: " . $e->getMessage());
        }

        return $this->getDefaultExampleBody();
    }

    private function generateExampleFromFormRequest($formRequestClass)
    {
        try {
            // Check if the FormRequest has constructor dependencies
            $reflector = new ReflectionClass($formRequestClass);
            $constructor = $reflector->getConstructor();

            $instance = null;

            if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
                // FormRequest has required constructor parameters
                // Try to resolve it from the container
                try {
                    $instance = app()->make($formRequestClass);
                } catch (\Exception $e) {
                    $this->warn("Cannot resolve FormRequest with dependencies: {$formRequestClass}");
                    return $this->getDefaultExampleBody();
                }
            } else {
                // No required constructor parameters, use createFrom
                $baseRequest = \Illuminate\Http\Request::create('/test', 'POST', []);
                $instance = $formRequestClass::createFrom($baseRequest);
            }

            if (!method_exists($instance, 'rules')) {
                return $this->getDefaultExampleBody();
            }

            // Wrap in try-catch to handle any runtime errors during rule generation
            try {
                $rules = $instance->rules();
            } catch (\Throwable $e) {
                $this->warn("Error invoking rules method: " . $e->getMessage());
                return $this->getDefaultExampleBody();
            }

            if (!is_array($rules)) {
                return $this->getDefaultExampleBody();
            }

            $example = [];

            foreach ($rules as $field => $fieldRules) {
                // Skip wildcard fields
                if (str_contains($field, '*')) {
                    continue;
                }

                $value = $this->generateExampleValue($field, $fieldRules);

                // Convert dot notation to nested array
                $this->setNestedValue($example, $field, $value);
            }

            return $example;
        } catch (\Exception $e) {
            $this->warn("Error generating example from FormRequest: " . $e->getMessage());
            return $this->getDefaultExampleBody();
        }
    }

    /**
     * Set a nested value in an array using dot notation
     */
    private function setNestedValue(&$array, $key, $value)
    {
        // If no dots, just set directly
        if (!str_contains($key, '.')) {
            $array[$key] = $value;
            return;
        }

        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $i => $k) {
            // Last key - set the value
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
                break;
            }

            // Create nested array if it doesn't exist
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }

            $current = &$current[$k];
        }
    }

    private function generateExampleValue($fieldName, $rules)
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        if (!is_array($rules)) {
            return $this->getValueByFieldName($fieldName);
        }

        foreach ($rules as $rule) {
            if (is_string($rule)) {
                if (str_contains($rule, 'email')) {
                    return 'superadmin@gmail.com';
                }

                if (str_contains($rule, 'boolean')) {
                    return true;
                }

                if (str_contains($rule, 'integer')) {
                    return 1;
                }

                if (str_contains($rule, 'numeric')) {
                    return 100;
                }

                if (str_contains($rule, 'array')) {
                    return [];
                }

                if (str_contains($rule, 'date')) {
                    return now()->format('Y-m-d');
                }

                if (preg_match('/in:([^,\]]+)/', $rule, $matches)) {
                    $values = array_map('trim', explode(',', $matches[1]));
                    return $values[0] ?? 'value';
                }
            }

            if (is_object($rule)) {
                $ruleClass = get_class($rule);

                if (str_contains($ruleClass, 'Password')) {
                    return 'Password@123';
                }

                // Safely handle __toString conversion
                try {
                    if (method_exists($rule, '__toString')) {
                        $ruleString = (string) $rule;
                        if (preg_match('/in:([^,\]]+)/', $ruleString, $matches)) {
                            $values = array_map('trim', explode(',', str_replace(['"', "'"], '', $matches[1])));
                            return $values[0] ?? 'value';
                        }
                    }
                } catch (\Throwable $e) {
                    // Ignore errors from __toString conversion
                    continue;
                }
            }
        }

        return $this->getValueByFieldName($fieldName);
    }

    private function getValueByFieldName($fieldName)
    {
        $lowerField = strtolower($fieldName);

        if (str_contains($lowerField, 'password') || str_contains($lowerField, 'confirm')) {
            return 'Password@123';
        }

        if (str_contains($lowerField, 'email')) {
            return 'user@example.com';
        }

        if (str_contains($lowerField, 'firstname') || $lowerField === 'first_name') {
            return 'John';
        }

        if (str_contains($lowerField, 'lastname') || $lowerField === 'last_name') {
            return 'Doe';
        }

        if (str_contains($lowerField, 'username')) {
            return 'superadmin';
        }

        if (str_contains($lowerField, 'name')) {
            return 'Example Name';
        }

        if (str_contains($lowerField, 'phone')) {
            return '+1234567890';
        }

        if (str_contains($lowerField, 'url') || str_contains($lowerField, 'website')) {
            return 'https://example.com';
        }

        if (str_contains($lowerField, 'description') || str_contains($lowerField, 'bio')) {
            return 'Example description';
        }

        if (str_contains($lowerField, 'role')) {
            return 'USER';
        }

        // Special handling for 'roles' field (array)
        if ($lowerField === 'roles') {
            return ['OWNER'];
        }

        if (str_ends_with($lowerField, 'ids') || str_ends_with($lowerField, '_ids')) {
            return [1, 2, 3];
        }

        if (str_ends_with($lowerField, 'id') || str_ends_with($lowerField, '_id')) {
            return 1;
        }

        return 'example_value';
    }

    private function getDefaultExampleBody()
    {
        return [
            'key_1' => 'value_1',
            'key_2' => 'value_2',
        ];
    }
}
