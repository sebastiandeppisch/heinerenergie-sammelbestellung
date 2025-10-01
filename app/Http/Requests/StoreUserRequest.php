<?php

namespace App\Http\Requests;

use App\Services\SessionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $sessionService = app(SessionService::class);
        return $sessionService->actsAsGroupAdmin() || $sessionService->actAsSystemAdmin(); 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'is_admin' => 'boolean',
            'first_name' => 'string|required',
            'last_name' => 'string|required',
            'email' => 'email|required|unique:users,email',
        ];
    }
}
