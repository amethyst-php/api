<?php

namespace Railken\LaraOre\Api\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return a JSON response with status success.
     *
     * @param array $data
     * @param int   $code
     *
     * @return \Illuminate\Http\Response
     */
    public function success($data = [], $code = 200)
    {
        return response()->json(array_merge([], $data), $code);
    }

    /**
     * Return a JSON response with status error.
     *
     * @param array $data
     * @param int   $code
     *
     * @return \Illuminate\Http\Response
     */
    public function error($data = [], $code = 400)
    {
        return response()->json(array_merge([], $data), $code);
    }

    /**
     * Return a JSON response with status error.
     *
     * @param array $data
     * @param int   $code
     *
     * @return \Illuminate\Http\Response
     */
    public function not_found($data = [], $code = 404)
    {
        return response()->json(array_merge(['message' => 'not found'], $data), $code);
    }

    /**
     * Return a JSON response.
     *
     * @param array $data
     * @param int   $code
     *
     * @return \Illuminate\Http\Response
     */
    public function response($data = [], $code = 200)
    {
        return response()->json($data, $code);
    }

    /**
     * Return a view.
     *
     * @param string $filename
     * @param array  $data
     * @param int    $code
     *
     * @return \Illuminate\Http\Response
     */
    public function view($view, $data = [], $code = 200)
    {
        $content = view($view, $data);
        $response = response($content, $code);
        $response->header('Content-Type', 'application/json');

        return $response;
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
