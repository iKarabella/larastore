<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderToAssemblyRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if(
            !$this->user() || 
            !array_key_exists($this->user()->id, config('app.market_rights')) ||
            !in_array('catalog', config('app.market_rights')[$this->user()->id])
        ) throw ValidationException::withMessages([
            'id' => ['Нельзя удалить'],
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
            'order_id'=>['numeric','required',Rule::exists('orders', 'id')->where(function (Builder $query) {
                $query->whereIn('status', [76,77,83]); //TODO по идее заказ с этими статусами не должен иметь резерва ReservedProduct
            })],
            'toAssembly'=>'array|required|min:1',
            'toAssembly.*.name'=>'string|required',
            'toAssembly.*.offer_id'=>'numeric|required|exists:product_offers,id',
            'toAssembly.*.product_id'=>'numeric|required|exists:products,id',
            'toAssembly.*.quantity'=>'numeric|required|min:1',
            'toAssembly.*.writeOffWh'=>'array|required|min:1',
            'toAssembly.*.writeOffWh.*.id'=>'numeric|required|exists:warehouses',
            'toAssembly.*.writeOffWh.*.quantity'=>'numeric|required|min:1'
        ];
    }

    public function attributes(): array
    {
        return [
            'order_id' => 'Заказ'
        ];
    }

    public function messages():array
    {
        return [
            'order_id.exists'=>'Заказ не найден, либо его статус не предполагает возможность изменения.',
        ];
    }
}
