<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use App\Services\Shipping\ShippingService;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderShippingStoreRequest extends FormRequest
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
            'order_id'=>['numeric', 'required', Rule::exists('orders', 'id')->where(function (Builder $query) {
                $query->whereStatus(76);
            })],
            'key'=>'string|nullable|in:'.implode(',', ShippingService::keys()),
            'amount'=>'decimal:2|nullable',
        ];
    }

    public function attributes(): array
    {
        return [
            'order_id' => 'Заказ',
        ];
    }

    public function messages():array
    {
        return [
            'order_id.exists'=>'Заказ не найден, либо его статус не предполагает возможность изменения.'
        ];
    }
}
