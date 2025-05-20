<?php

namespace App\Modules\Larastore\Http\Requests;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'total_sum'=>'decimal:2|required',
            'positions'=>'array|nullable|min:1',
            'positions.*.product'=>['numeric','required', Rule::exists('products', 'id')->where(function (Builder $query) {
                return $query->whereVisibility(1);
            })],
            'positions.*.offer'=>['numeric','required', Rule::exists('product_offers', 'id')->where(function (Builder $query) {
                return $query->whereVisibility(1);
            })],
            'positions.*.quantity'=>'numeric|required|min:1',
            'customer'=>'array|required',
            'customer.name'=>'string|min:2|max:25',
            'customer.patronymic'=>'nullable|string|max:25',
            'customer.surname'=>'required|string|min:2|max:25',
            'customer.phone'=>'required|string',
            'delivery'=>'array|required',
            'delivery.region'=>'nullable|string|min:2|max:35',
            'delivery.city'=>'required|string|min:2|max:35',
            'delivery.street'=>'required|string|min:2|max:35',
            'delivery.house'=>'required|string|min:2|max:35',
            'delivery.apartment'=>'nullable|string|min:2|max:35',
            'code'=>'nullable|string|max:35'
        ];
    }

    public function attributes(): array
    {
        return [
            'positions' => 'Состав заказа',
        ];
    }
}
