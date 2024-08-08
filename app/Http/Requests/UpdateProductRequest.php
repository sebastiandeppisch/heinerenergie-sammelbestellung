<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->isActingAsAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'string',
            'price' => 'numeric',
            'panelsCount' => 'integer|min:0',
            'sku' => 'string',
            'url' => 'url',
            'description' => 'string',
            'product_category_id' => 'integer',
            'is_supplier_product' => 'boolean',
        ];
    }
}
