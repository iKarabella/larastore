<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreOfferRequest extends FormRequest
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
            'id'=>'numeric|nullable|exists:product_offers',
            'product_id'=>['numeric','required','exists:products,id', Rule::excludeIf($this->id>0)],
            'title'=>'string|required|min:1',
            'baseprice'=>'decimal:2|nullable',
            'price'=>'decimal:2|required|min:0.01',
            'barcode'=>'string|nullable',
            'art'=>'string|nullable',
            'visibility'=>'boolean',
            'to_caschier'=>'boolean',
            'weight'=>'integer|nullable',
            'length'=>'integer|nullable',
            'height'=>'integer|nullable',
            'width'=>'integer|nullable',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Категория',
            'title'=>'Название',
            'baseprice'=>'Стоимость',
            'price'=>'Цена',
            'barcode'=>'Штрихкод',
            'art'=>'Артикул',
            'visibility'=>'Видимость',
            'to_caschier'=>'Передавать в кассу',
            'weight'=>'Масса',
            'length'=>'Длина',
            'height'=>'Высота',
            'width'=>'Ширина',
        ];
    }

    public function messages()
    {
        return [
            'price.decimal' => 'Число формата 0.00',
            'baseprice.decimal' => 'Число формата 0.00',
        ];
    }
}
