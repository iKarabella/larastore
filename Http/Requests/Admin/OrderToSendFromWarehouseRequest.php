<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use Auth;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderToSendFromWarehouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        if(
            !$this->user() || 
            !array_key_exists($this->user()->id, config('app.market_rights')) ||
            !in_array('warehouse', config('app.market_rights')[$this->user()->id])
        ) throw ValidationException::withMessages([
            'id' => ['Нельзя изменить'],
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
                $query->whereIn('status', [78,79,87,88,89]);
            })],
            'track' => 'string|nullable',
            'warehouse_id' => 'numeric|required|exists:warehouses,id'
        ];
    }

    public function attributes(): array
    {
        return [
            'order_id' => 'Заказ',
            'warehouse_id'  => 'Склад'
        ];
    }

    public function messages():array
    {
        return [
            'order_id.exists'=>'Заказ не найден, либо его статус не предполагает возможность изменения.',
        ];
    }
}
