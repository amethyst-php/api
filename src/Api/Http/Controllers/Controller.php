<?php

namespace Railken\LaraOre\Api\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Return a new JSON response from the application.
     *
     * @param string|array $data
     * @param int          $status
     * @param array        $headers
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @static
     */
    public function response($data = [], $status = 200, $headers = [])
    {
        return new JsonResponse($data, $status, $headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Retrieve user.
     *
     * @return mixed
     */
    public function getUser()
    {
        return Auth::user();
    }
}
