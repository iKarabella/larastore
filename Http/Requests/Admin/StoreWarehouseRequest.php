<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use App\Services\Caschier\CaschierService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreWarehouseRequest extends FormRequest
{
    private $addRules=[];
    private $addAttributes=[];

    protected function prepareForValidation(): void
    {
        if(
            !$this->user() || 
            !array_key_exists($this->user()->id, config('app.market_rights')) ||
            !in_array('warehouse', config('app.market_rights')[$this->user()->id])
        ) throw ValidationException::withMessages([
            'id' => ['Нельзя редактировать'],
        ]);

        if($this->caschier)
        {
            $cashier = CaschierService::rules($this->caschier, 'caschier_settings');
            $this->addRules = [...$this->addRules, ...$cashier['rules']];
            $this->addAttributes = [...$this->addRules, ...$cashier['attributes']];
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'id'=>'numeric|nullable|exists:warehouses',
            'title'=>'string|required',
            'code'=>['string','required', Rule::unique('warehouses')->ignore($this->id, 'id')],
            'phone'=>'string|required',
            'address'=>'string|required',
            'caschier' => ['nullable','string', Rule::in(CaschierService::servicesList(true))],
            'school_id'=>'numeric|nullable|exists:schools,id',
            'description'=>'string|nullable'
        ];
            
        if (count($this->addRules)) $rules = array_merge($rules, $this->addRules);

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [
            'id' => 'Категория',
        ];

        if (count($this->addAttributes)) $attributes = array_merge($attributes, $this->addAttributes);

        return $attributes;
    }
}
