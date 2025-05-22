<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrdersListRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if(
            !$this->user() || 
            !array_key_exists($this->user()->id, config('app.market_rights')) ||
            !in_array('orders', config('app.market_rights')[$this->user()->id])
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
           'filters'=>'array|nullable',
           'filters.statuses'=>'array|nullable',
           'filters.statuses.*.status'=>'numeric|required|exists:site_entities_values,id',
           'filters.statuses.*.on'=>'boolean|required',
           'filters.dates'=>'array|nullable|min:2',
           'filters.dates.*'=>'date|nullable',
           'filters.sortDesc'=>'boolean|nullable'
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
            'order_id.exists'=>'Заказ не найден, либо его статус не предполагает возможность изменения.'
        ];
    }
}
