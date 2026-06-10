<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Form;
use App\Models\FormElement;
use App\Services\FormService;
use Illuminate\Http\Request;

class FormController extends BaseController
{
    protected $filterKey = null;
    protected $service;

    public function __construct(FormService $service)
    {
        parent::__construct(new Form());
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate($this->model->getValidationRules('store'));
        
        $form = Form::create([
            'name' => $validatedData['name'],
            'type' => $validatedData['type'],
        ]);

        $elements = $validatedData['elements'] ?? [];
        if (!empty($elements) && is_array($elements)) {
            foreach ($elements as $index => $element) {
                if (isset($element['type']) && isset($element['title'])) {
                    $form->elements()->create([
                        'type' => $element['type'],
                        'title' => $element['title'],
                        'order' => $element['order'] ?? ($index + 1),
                    ]);
                }
            }
        }

        return response()->json([
            'id' => $form->id,
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
                'type' => $form->type,
                'elements' => $form->elements->map(function ($element) {
                    return [
                        'id' => $element->id,
                        'type' => $element->type,
                        'title' => $element->title,
                        'order' => $element->order,
                    ];
                }),
                'created_at' => $form->created_at,
                'updated_at' => $form->updated_at,
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate($this->model->getValidationRules('update'));
        
        $form = Form::find($id);
        if (!$form) {
            return response()->json(['message' => 'Record not found'], 404);
        }

        $form->update([
            'name' => $validatedData['name'],
            'type' => $validatedData['type'],
        ]);

        $elements = $validatedData['elements'] ?? [];
        if (isset($elements)) {
            $form->elements()->delete();
            
            if (!empty($elements) && is_array($elements)) {
                foreach ($elements as $index => $element) {
                    if (isset($element['type']) && isset($element['title'])) {
                        $form->elements()->create([
                            'type' => $element['type'],
                            'title' => $element['title'],
                            'order' => $element['order'] ?? ($index + 1),
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
                'type' => $form->type,
                'elements' => $form->elements->map(function ($element) {
                    return [
                        'id' => $element->id,
                        'type' => $element->type,
                        'title' => $element->title,
                        'order' => $element->order,
                    ];
                }),
                'created_at' => $form->created_at,
                'updated_at' => $form->updated_at,
            ]
        ], 200);
    }

    public function clockInQuestionnaires(Request $request)
    {
        $forms = Form::where('type', 'clock_in')
            ->orWhere('type', 'questionnaire')
            ->with('elements')
            ->get();
        return response()->json($this->resource::collection($forms), 200);
    }

    public function updateElementOrder(\Illuminate\Http\Request $request, $id)
    {
        $request->validate([
            'form_element_id' => 'required|exists:form_elements,id,form_id,' . $id,
            'direction' => 'required|in:up,down',
        ]);

        $result = $this->service->updateElementOrder($id, $request->only(['form_element_id', 'direction']));
        if ($result['success']) {
            return response()->json(['success' => true], 200);
        }

        return response()->json(['success' => false, 'error' => $result['error'],  'error_code' => $result['error_code']], $result['error_code']);
    }
}
