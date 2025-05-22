<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RemoveMediaRequest extends FormRequest
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
            'id'=>'numeric|required|exists:product_media'
        ];
    }
}
