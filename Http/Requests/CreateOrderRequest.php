<?php

namespace App\Modules\Larastore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'total'=>'decimal:2|required',
            'positions'=>'array|nullable|min:1',
            'positions.*.product'=>'numeric|required|exists:products,id',
            'positions.*.offer'=>'numeric|required|exists:product_offers,id',
            'positions.*.quantity'=>'numeric|required|min:1'
        ];
    }

    public function attributes(): array
    {
        return [
            'positions' => 'Состав заказа',
        ];
    }
}
