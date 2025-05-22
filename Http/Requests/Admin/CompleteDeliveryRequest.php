<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CompleteDeliveryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if(
            !$this->user() || 
            !array_key_exists($this->user()->id, config('app.market_rights')) ||
            !in_array('delivery', config('app.market_rights')[$this->user()->id])
        ) throw ValidationException::withMessages([
            'id' => ['Нельзя править'],
        ]);

        $this->merge([
            'user_id'=>$this->user()->id,
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
            'shipping'=>['numeric','required', Rule::exists('shippings', 'id')->where(function (Builder $query) {
                $query->whereStatus(91);
            })],
            "comment" => 'string|nullable|required_if:delivered,false',
            "delivered" => 'boolean|required',
            "returnedToWarehouse" => 'boolean|required_if:delivered,false'
        ];
    }

    public function messages():array
    {
        return [
            'shipping.exists'=>'Заказ не найден, либо его статус не предполагает возможность изменения.',
            'comment.required_if'=>'Указание причины необходимо, если заказ не доставлен.'
        ];
    }
}
