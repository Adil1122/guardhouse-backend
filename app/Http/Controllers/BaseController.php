<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BaseController extends Controller
{
    protected $model;
    protected $resource;
    protected $perPage = 10;
    protected $filterKey = 'user_id';

    public function __construct($model)
    {
        $this->model = $model;
        $this->resource = $this->model::getResource();
    }

    public function getFilterId(Request $request)
    {
        if (!$this->filterKey) {
            return null;
        }

        if ($request->has($this->filterKey)) {
            return $request->input($this->filterKey);
        }

        return null;
    }

    public function index(Request $request)
    {
        $filter = $request->route('filter', '');
        $query = $this->model::query();
        
        $filterId = $this->getFilterId($request);
        if ($filterId) {
            $query->where($this->filterKey, $filterId);
        }

        if (method_exists($this->model, 'scopeApplyFilter')) {
            $query->applyFilter($filter, $filterId);
        }

        return response()->json($this->resource::collection($query->get()), 200);
    }

    public function paginatedList(Request $request)
    {
        $page = $request->route('page');
        $filter = $request->route('filter', '');

        $query = $this->model::query();
        
        $filterId = $this->getFilterId($request);
        if ($filterId) {
            $query->where($this->filterKey, $filterId);
        }

        if (method_exists($this->model, 'scopeApplyFilter')) {
            $query->applyFilter($filter, $filterId);
        }

        $paginator = $query->orderBy('id', 'desc')->paginate($this->perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $this->resource::collection($paginator->items()),
            'current_page' => $paginator->currentPage(),
            'total_pages' => $paginator->lastPage(),
            'total_items' => $paginator->total(),
            'per_page' => $paginator->perPage()
        ]);
    }

    public function show(Request $request, $id)
    {
        $record = $this->model::find($id);

        if (!$record) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $filterId = $this->getFilterId($request);
        if ($filterId && $record->{$this->filterKey} !== $filterId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json(new $this->resource($record), 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate($this->model->getValidationRules('store'));

        $filterId = $this->getFilterId($request);
        if ($filterId) {
            $validatedData[$this->filterKey] = $filterId;
        }

        $preCreatedEvent = $this->model->preCreatedEvent ?? null;
        if ($preCreatedEvent) {
            Event::dispatch(new $preCreatedEvent($validatedData));
        }

        $record = $this->model::create($validatedData);

        $createdEvent = $this->model->createdEvent ?? null;
        if ($createdEvent && !$this->model->skipCreatedEvent) {
            Event::dispatch(new $createdEvent($record));
        }

        return response()->json(['id' => $record->id], 201);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate($this->model->getValidationRules('update'));
        $record = $this->model::find($request->route('id'));

        if (!$record) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $oldData = $record->toArray();
        $record->update($validatedData);

        $updatedEvent = $this->model->updatedEvent ?? null;
        if ($updatedEvent && !$this->model->skipUpdatedEvent) {
            Event::dispatch(new $updatedEvent($record, $validatedData, $oldData));
        }

        return response()->json([], 200);
    }

    public function destroy(Request $request, $id)
    {
        $record = $this->model::find($request->route('id'));

        if (!$record) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $filterId = $this->getFilterId($request);
        if ($filterId && $record->{$this->filterKey} !== $filterId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $data = $record->toArray();
            $record->delete();

            $deletedEvent = $this->model->deletedEvent ?? null;
            if ($deletedEvent) {
                Event::dispatch(new $deletedEvent($data));
            }
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Cannot delete this record because it is linked to another record'
                ], 409);
            }
        
            throw $e;
        }

        return response()->noContent();
    }

    public function destroyMany(Request $request)
    {
        $filterKey = $this->filterKey;
        $filterId  = $this->getFilterId($request);

        $request->validate([
            'ids'   => 'required|array',
            'ids.*'  => ['integer',
                Rule::exists($this->model->getTable(), 'id')->where(function ($query) use ($filterKey, $filterId) {
                    return $query->where($filterKey, $filterId);
                }),
            ],
        ]);

        try {
            $records = $this->model::whereIn('id', $request->input('ids'))->where($filterKey, $filterId)->get();

            foreach ($records as $record) {
                $data = $record->toArray();
                $record->delete();

                $deletedEvent = $this->model->deletedEvent ?? null;
                if ($deletedEvent) {
                    Event::dispatch(new $deletedEvent($data));
                }
            }

            return response()->noContent();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Cannot delete one or more records because they are linked to other records'
                ], 409);
            }

            throw $e;
        }
    }

}
