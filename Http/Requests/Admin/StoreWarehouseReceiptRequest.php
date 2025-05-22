<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreWarehouseReceiptRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if(
            !$this->user() || 
            !array_key_exists($this->user()->id, config('app.market_rights')) ||
            !in_array('warehouse', config('app.market_rights')[$this->user()->id])
        ) throw ValidationException::withMessages([
            'id' => ['Нельзя редактировать'],
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
            'warehouse'=>'numeric|required|exists:warehouses,id',
            'items'=>'array|required|min:1',
            'items.*'=>'array|required',
            'items.*.offer_id'=>'numeric|required|exists:product_offers,id',
            'items.*.price'=>'decimal:2|required',
            'items.*.quantity'=>'integer|required|min:1'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Категория',
        ];
    }
}
