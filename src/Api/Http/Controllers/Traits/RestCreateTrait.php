<?php

namespace Railken\LaraOre\Api\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

trait RestCreateTrait
{
    /**
     * Create a new resource.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $manager = $this->manager;

        $result = $manager->create($request->only($this->keys->fillable));

        if (!$result->ok()) {
            return $this->response(['errors' => $result->getSimpleErrors()], Response::HTTP_BAD_REQUEST);
        }

        return $this->success($this->serialize($result->getResource(), $request), Response::HTTP_CREATED);
    }
}
