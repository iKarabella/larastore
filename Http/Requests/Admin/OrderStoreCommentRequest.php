<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use Auth;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderStoreCommentRequest extends FormRequest
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
            !in_array('catalog', config('app.market_rights')[$this->user()->id])
        ) throw ValidationException::withMessages([
            'id' => ['Нельзя удалить'],
        ]);

        $this->merge([
            'user_id'=>$this->user()->id,
            'auto'=>false,
            'title'=>'Пользователь <a href="'.route('user.page', [$this->user()->login]).'" target="_blank" title="'.implode(' ', [$this->user()->surname, $this->user()->name, $this->user()->patronymic]).'">'.$this->user()->login.'</a> оставил комментарий:'
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
                $query->whereNotIn('status', [81,82]);
            })],
            'user_id'=>'numeric|required',
            'title'=>'string|nullable',
            'comment'=>'string|required|min:1',
        ];
    }

    public function attributes(): array
    {
        return [
            'order_id' => 'Заказ',
            'title' =>'Заголовок',
            'comment'  => 'Комментарий'
        ];
    }

    public function messages():array
    {
        return [
            'order_id.exists'=>'Заказ не найден, либо его статус не предполагает возможность изменения.',
        ];
    }
}
