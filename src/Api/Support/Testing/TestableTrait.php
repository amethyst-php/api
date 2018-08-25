<?php
namespace Railken\LaraOre\Api\Support\Testing;

trait TestableTrait
{
    public function commonTest($url, $parameters, $check = null)
    {
        if (!$check) {
            $check = $parameters;
        }
        
        // GET /
        $response = $this->get($url, []);
        $this->assertOrPrint($url, $response, 200);

        // GET /
        $response = $this->get($url, ['query' => 'id eq 1']);
        $this->assertOrPrint($url, $response, 200);

        // POST /
        $response = $this->post($url, $parameters->toArray());
        $this->assertOrPrint($url, $response, 201);
        $resource = json_decode($response->getContent())->resource;

        // GET /id
        $response = $this->get($url.'/'.$resource->id);
        $this->assertOrPrint($url, $response, 200);

        // PUT /id
        $response = $this->put($url.'/'.$resource->id, $parameters->toArray());
        $resource = json_decode($response->getContent())->resource;
        $this->assertOrPrint($url, $response, 200);

        // DELETE /id
        $response = $this->delete($url.'/'.$resource->id);
        $this->assertOrPrint($url, $response, 204);
        $response = $this->get($url.'/'.$resource->id);
        $this->assertOrPrint($url, $response, 404);
    }

    public function assertOrPrint($url, $response, $code)
    {
        if ($response->getStatusCode() !== $code) {
            print_r($url);
            print_r($response->getContent());
        }

        $response->assertStatus($code);
    }
}
