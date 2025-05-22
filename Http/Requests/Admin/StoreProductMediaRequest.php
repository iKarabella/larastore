<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\File;

class StoreProductMediaRequest extends FormRequest
{

    protected function prepareForValidation(): void
    {
        if(
            !$this->user() || 
            !array_key_exists($this->user()->id, config('app.market_rights')) ||
            !in_array('products', config('app.market_rights')[$this->user()->id])
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
            'product_id'=>'numeric|required_without:offer_id|nullable|exists:products,id',
            'offer_id'=>'numeric|required_without:product_id|nullable|exists:product_offers,id',
            'files'=>'array|min:1',
            'files.*'=>File::image()->max('5mb')->dimensions(Rule::dimensions()->minWidth(350)->minHeight(350)->maxWidth(3500)->maxHeight(3500))
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Категория',
            'title'=>'Название',
            'code'=>'Код ссылки',
            'description'=>'Описание',
            'visibility'=>'Видимость',
            'parent'=>'Родительская категория'
        ];
    }
}
