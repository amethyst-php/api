<?php

namespace Railken\LaraOre\Api\Support\Testing;

use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

trait TestableBaseTrait
{
    /**
     * Retrieve basic url.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return Config::get('ore.api.http.'.$this->group.'.router.prefix');
    }

    /**
     * Retrieve resource url.
     *
     * @return string
     */
    public function getResourceUrl()
    {
        return $this->getBaseUrl().Config::get($this->config.'.http.'.$this->group.'.router.prefix');
    }

    /**
     * Test common requests.
     */
    public function testSuccessCommon()
    {
        $this->commonTest($this->getResourceUrl(), $this->faker::make()->parameters());
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
            'Accept'       => 'application/json',
        ]);

        $response = $this->callAndTest('POST', $url, $parameters->toArray(), Response::HTTP_CREATED);
        $resource = json_decode($response->getContent())->data;

        $response = $this->callAndTest('GET', $url, [], Response::HTTP_OK);
        $response = $this->callAndTest('GET', $url, ['query' => 'id eq 1'], Response::HTTP_OK);
        $response = $this->callAndTest('GET', $url.'/'.$resource->id, [], Response::HTTP_OK);
        $response = $this->callAndTest('PUT', $url.'/'.$resource->id, $parameters->toArray(), Response::HTTP_OK);
        $resource = json_decode($response->getContent())->data;

        $response = $this->callAndTest('DELETE', $url.'/'.$resource->id, [], Response::HTTP_NO_CONTENT);
        $response = $this->callAndTest('GET', $url.'/'.$resource->id, [], Response::HTTP_NOT_FOUND);
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
