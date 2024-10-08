<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductRequest extends FormRequest
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
            'name' => 'required|string',
            'price' => 'required|numeric',
            'panelsCount' => 'required|integer|min:0',
            'sku' => 'nullable|string',
            'description' => 'nullable|string',
            'product_category_id' => 'nullable|integer',
            'is_supplier_product' => 'nullable|boolean',
        ];
    }
}
