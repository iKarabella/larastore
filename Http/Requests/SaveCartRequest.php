<?php

namespace App\Modules\Larastore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SaveCartRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'cart'=>'array|nullable',
            'cart.*'=>'array|required',
            'cart.*.position'=>'numeric|required|exists:products,id',
            'cart.*.offer'=>'numeric|required|exists:product_offers,id',
            'cart.*.quantity'=>'numeric|required|min:1'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Корзина',
        ];
    }
}
