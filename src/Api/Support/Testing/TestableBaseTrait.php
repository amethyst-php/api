<?php

namespace Railken\Amethyst\Api\Support\Testing;

use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

trait TestableBaseTrait
{
    /**
     * Retrieve routes enabled.
     *
     * @return array
     */
    public function getRoutes()
    {
        return property_exists($this, 'routes') ? $this->routes : ['index', 'show', 'create', 'update', 'remove'];
    }

    /**
     * Check route.
     *
     * @param string $route
     *
     * @return bool
     */
    public function checkRoute(string $name): bool
    {
        return in_array($name, $this->getRoutes());
    }

    /**
     * Retrieve basic url.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return Config::get('ore.api.http.'.$this->group.'.router.prefix');
    }

    /**
     * Retrieve resource url.
     *
     * @return string
     */
    public function getResourceUrl(): string
    {
        return $this->getBaseUrl().Config::get($this->config.'.router.prefix');
    }

    /**
     * Test common requests.
     */
    public function testSuccessCommon()
    {
        $this->commonTest($this->getResourceUrl(), $this->faker::make()->parameters());
    }

    /**
     * Retrieve a resource.
     *
     * @param string $url
     */
    public function retrieveResource(string $url)
    {
        if (!$this->checkRoute('index')) {
            throw new \Exception('Index route should be enabled to retrieve a resource for update, remove and show');
        }

        $response = $this->callAndTest('GET', $url, [], Response::HTTP_OK);

        return json_decode($response->getContent())->data[0];
    }

    /**
     * Test common.
     *
     * @param string       $url
     * @param \Railken\Bag $parameters
     * @param bool         $check
     */
    public function commonTest($url, $parameters, $check = null)
    {
        if (!$check) {
            $check = $parameters;
        }

        $this->withHeaders([
            'Accept'             => 'application/json',
            'Content-Type'       => 'application/json',
        ]);

        if ($this->checkRoute('create')) {
            $response = $this->callAndTest('POST', $url, $parameters->toArray(), Response::HTTP_CREATED);
        }

        if ($this->checkRoute('index')) {
            $response = $this->callAndTest('GET', $url, [], Response::HTTP_OK);
            $response = $this->callAndTest('GET', $url, ['query' => 'id eq 1'], Response::HTTP_OK);
        }

        if ($this->checkRoute('show')) {
            $resource = $this->retrieveResource($url);
            $response = $this->callAndTest('GET', $url.'/'.$resource->id, [], Response::HTTP_OK);
        }

        if ($this->checkRoute('update')) {
            $resource = $this->retrieveResource($url);
            $response = $this->callAndTest('PUT', $url.'/'.$resource->id, $parameters->toArray(), Response::HTTP_OK);
        }

        if ($this->checkRoute('remove')) {
            $resource = $this->retrieveResource($url);
            $response = $this->callAndTest('DELETE', $url.'/'.$resource->id, [], Response::HTTP_NO_CONTENT);
        }
    }

    /**
     * Make the call and test it.
     *
     * @param string $method
     * @param string $url
     * @param array  $parameters
     * @param int    $code
     */
    public function callAndTest($method, $url, $parameters, $code)
    {
        $response = $this->call($method, $url, $parameters);

        $this->printCall($method, $url, $parameters, $response, $code);

        $response->assertStatus($code);

        return $response;
    }

    /**
     * Print the call.
     *
     * @param string $method
     * @param string $url
     * @param array  $parameters
     * @param mixed  $response
     * @param int    $code
     */
    public function printCall($method, $url, $parameters, $response, $code)
    {
        print_r("\n\n----------------------------------------------------------------");
        print_r(sprintf("\n%s %s", $method, $url));
        print_r(sprintf("\n\nParameters Sent:\n%s", json_encode($parameters, JSON_PRETTY_PRINT)));
        print_r(sprintf("\n\nResponse Status Code: %s", $response->getStatusCode()));
        print_r(sprintf("\n\nResponse Body:\n%s\n", json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT)));
    }
}
