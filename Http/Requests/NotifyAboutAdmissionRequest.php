<?php

namespace App\Modules\Larastore\Http\Requests;

use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class NotifyAboutAdmissionRequest extends FormRequest
{
    /**
     * Подготовка к проверке данных
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::check()?Auth::user()->id:null
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
            'user_id'=>'numeric|nullable',
            'product_id'=>'numeric|required|exists:products,id',
            'offer_id'=>'numeric|required|exists:product_offers,id',
            'name'=>'string|required_without:user_id|nullable',
            'email'=>'required_without:user_id|nullable|email:rfc,dns'
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'Пользователь',
        ];
    }

    public function messages()
    {
        return [
            'name.required_without'=>'Имя необходимо, если вы не авторизованы',
            'email.required_without'=>'E-mail необходим, если вы не авторизованы'
        ];
    }
}
