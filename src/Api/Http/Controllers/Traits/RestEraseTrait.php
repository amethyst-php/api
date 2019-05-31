<?php

namespace Railken\Amethyst\Api\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Railken\LaraEye\Filter;
use Railken\Lem\Result;
use Railken\SQ\Exceptions\QuerySyntaxException;
use Symfony\Component\HttpFoundation\Response;

trait RestEraseTrait
{
    /**
     * Display resources.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function erase(Request $request)
    {
        $this->iniByRequest($request);
        
        $query = $this->getQuery();

        try {
            if ($request->input('query')) {
                $filter = new Filter($this->manager->newEntity()->getTable(), $this->queryable);
                $filter->build($query, $request->input('query'));
            }
        } catch (QuerySyntaxException $e) {
            return $this->error(['code' => 'QUERY_SYNTAX_ERROR', 'message' => 'Syntax error']);
        }

        $params = $request->only($this->fillable);

        DB::beginTransaction();

        $result = new Result();

        $query->chunk(100, function ($resources) use ($params, &$result) {
            foreach ($resources as $resource) {
                $result->addErrors($this->getManager()->remove($resource)->getErrors());
            }
        });

        if (!$result->ok()) {
            DB::rollBack();

            return $this->response(['errors' => $result->getSimpleErrors()], Response::HTTP_BAD_REQUEST);
        }

        DB::commit();

        return $this->response(null, Response::HTTP_OK);
    }
}
