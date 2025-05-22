<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreProductRequest extends FormRequest
{

    protected function prepareForValidation(): void
    {
        if(
            !$this->user() || 
            !array_key_exists($this->user()->id, config('app.market_rights')) ||
            !in_array('catalog', config('app.market_rights')[$this->user()->id])
        ) throw ValidationException::withMessages([
            'id' => ['Нельзя править'],
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'id'=>'numeric|nullable|exists:products',
            'title'=>'string|required|min:5',
            'link'=>['string','required', Rule::unique('products')->ignore($this->id)],
            'short_description'=>'string|required|min:5',
            'description'=>'string|required|min:5',
            'visibility'=>'boolean',
            'offersign'=>'string|nullable',
            'categories'=>'array|required|min:1',
            'categories.*'=>'array',
            'categories.*.id'=>'numeric|required|exists:catalog_cats',
            'measure'=>'numeric|required|exists:site_entities_values,id'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Категория',
            'title'=>'Название',
            'link'=>'Код ссылки',
            'short_description'=>'Краткое описание',
            'description'=>'Описание',
            'visibility'=>'Видимость'
        ];
    }
}
