<?php

namespace App\Modules\Larastore\Http\Requests\Admin;

use App\Models\CatalogCat;
use App\Traits\StringTrait;
use DB;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreCatRequest extends FormRequest
{
    use StringTrait;

    protected function prepareForValidation(): void
    {
        if(
            !$this->user() || 
            !array_key_exists($this->user()->id, config('app.market_rights')) ||
            !in_array('catalog', config('app.market_rights')[$this->user()->id])
        ) throw ValidationException::withMessages([
            'id' => ['Нельзя править'],
        ]);

        if(empty($this->code) && !empty($this->title)){
            $code = $this->translit($this->title, true, true);

            $counter = DB::table('catalog_cats')->whereCode($code)->count();
            if ($counter>0) $code = $counter.$code;
            
            $this->merge([
                'code'=>$code,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        if ($this->id) $rule = ['string','required', 'min:3', 'max:25', Rule::unique(CatalogCat::class)->ignore($this->id)];
        else $rule = 'string|required|min:3|max:25|unique:catalog_cats,code';

        return [
            'id'=>'numeric|nullable|exists:catalog_cats',
            'title'=>'string|min:3',
            'code'=>$rule,
            'description'=>'string|nullable',
            'visibility'=>'boolean',
            'parent'=>'numeric|nullable|exists:catalog_cats,id'
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'Категория',
            'title'=>'Название',
            'code'=>'Код ссылки',
            'description'=>'Описание',
            'visibility'=>'Видимость',
            'parent'=>'Родительская категория'
        ];
    }
}
