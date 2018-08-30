<?php

namespace Railken\LaraOre\Api\Support\Testing;

trait TestableTrait
{
    public function commonTest($url, $parameters, $check = null)
    {
        if (!$check) {
            $check = $parameters;
        }

        $this->withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        // POST /
        $response = $this->post($url, $parameters->toArray());
        $this->assertOrPrint('POST', $url, $parameters, $response, 201);
        $resource = json_decode($response->getContent())->data;

        // GET /
        $response = $this->get($url, []);
        $this->assertOrPrint('GET', $url, $parameters, $response, 200);

        // GET /
        $response = $this->get($url, ['query' => 'id eq 1']);
        $this->assertOrPrint('GET', $url, $parameters, $response, 200);

        // GET /id
        $response = $this->get($url.'/'.$resource->id);
        $this->assertOrPrint('GET', $url, $parameters, $response, 200);

        // PUT /id
        $response = $this->put($url.'/'.$resource->id, $parameters->toArray());
        $resource = json_decode($response->getContent())->data;
        $this->assertOrPrint('PUT', $url, $parameters, $response, 200);

        // DELETE /id
        $response = $this->delete($url.'/'.$resource->id);
        $this->assertOrPrint('DELETE', $url, $parameters, $response, 204);
        $response = $this->get($url.'/'.$resource->id);
        $this->assertOrPrint('GET', $url, $parameters, $response, 404);
    }

    public function assertOrPrint($method, $url, $parameters, $response, $code)
    {
        print_r("\n\n----------------------------------------------------------------");
        print_r(sprintf("\n%s %s", $method, $url));
        print_r(sprintf("\n\nParameters Sent:\n%s", json_encode($parameters->toArray(), JSON_PRETTY_PRINT)));
        print_r(sprintf("\n\nResponse Status Code: %s", $response->getStatusCode()));
        print_r(sprintf("\n\nResponse Body:\n%s", json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT)));

        // if ($response->getStatusCode() !== $code) {
        // }

        $response->assertStatus($code);
    }
}
