<?php

namespace Railken\Amethyst\Api\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Railken\Amethyst\Api\Support\Exceptions\InvalidSorterFieldException;
use Railken\Amethyst\Api\Support\Sorter;
use Railken\LaraEye\Filter;
use Railken\SQ\Exceptions\QuerySyntaxException;
use Symfony\Component\HttpFoundation\Response;

trait RestIndexTrait
{
    /**
     * Display resources.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->createIndexResponseByQuery($this->getQuery(), $request);
    }

    public function createIndexResponseByQuery($query, Request $request)
    {
        if ($request->input('sort')) {
            $sorter = new Sorter();
            $sorter->setKeys($this->keys->sortable->toArray());

            try {
                foreach (explode(',', $request->input('sort')) as $sort) {
                    if (substr($sort, 0, 1) === '-') {
                        $sorter->add(substr($sort, 1), 'desc');
                    } else {
                        $sorter->add($sort, 'asc');
                    }
                }
            } catch (InvalidSorterFieldException $e) {
                return $this->response(['errors' => [['code' => 'SORT_INVALID_FIELD', 'message' => 'Invalid field for sorting']]], Response::HTTP_BAD_REQUEST);
            }

            foreach ($sorter->get() as $attribute) {
                $query->orderBy($this->parseKey($attribute->getName()), $attribute->getDirection());
            }
        }

        $selectable = $this->getSelectedAttributesByRequest($request);

        try {
            if ($request->input('query')) {
                $filter = new Filter($this->manager->newEntity()->getTable(), $selectable);
                $filter->build($query, $request->input('query'));
            }
        } catch (QuerySyntaxException $e) {
            return $this->error(['code' => 'QUERY_SYNTAX_ERROR', 'message' => 'Syntax error']);
        }

        $result = $query->paginate($request->input('show', 10), ['*'], 'page', $request->input('page'));

        $resources = $result->getCollection();

        return $this->response($this->serializeCollection($resources, $request, $result));
    }
}
