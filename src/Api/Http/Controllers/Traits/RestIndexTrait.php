<?php

namespace Railken\LaraOre\Api\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Railken\LaraEye\Filter;
use Railken\LaraOre\Api\Support\Exceptions\InvalidSorterFieldException;
use Railken\LaraOre\Api\Support\Paginator;
use Railken\LaraOre\Api\Support\Sorter;
use Railken\SQ\Exceptions\QuerySyntaxException;

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
        // FilterSyntaxException

        // Sorter
        $sort = new Sorter();
        $sort->setKeys($this->keys->sortable->toArray());

        try {
            $sort->add($request->input('sort_field', 'id'), strtolower($request->input('sort_direction', 'desc')));
        } catch (InvalidSorterFieldException $e) {
            return $this->error(['code' => 'SORT_INVALID_FIELD', 'message' => 'Invalid field for sorting']);
        }

        foreach ($sort->get() as $attribute) {
            $query->orderBy($this->parseKey($attribute->getName()), $attribute->getDirection());
        }

        $selectable = $this->getSelectedAttributesByRequest($request);

        try {
            if ($request->input('query')) {
                $filter = new Filter($this->manager->newEntity()->getTable(), $selectable->toArray());
                $filter->build($query, $request->input('query'));
            }
        } catch (QuerySyntaxException $e) {
            return $this->error(['code' => 'QUERY_SYNTAX_ERROR', 'message' => 'Syntax error']);
        }

        // Pagination
        $paginator = new Paginator();
        $paginator = $paginator->paginate($query->count(), $request->input('page', 1), $request->input('show', 10));

        $resources = $query
            ->skip($paginator->get('skip'))
            ->take($paginator->get('take'))
            // ->select($selectable->toArray())
            ->get();

        return $this->response($this->serializeCollection($resources, $request));
    }
}
