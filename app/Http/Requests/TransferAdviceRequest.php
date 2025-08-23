<?php

namespace App\Http\Requests;

use App\Models\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class TransferAdviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $targetGroup = Group::where('uuid', $this->group_id)->firstOrFail();
        if (! $targetGroup->accepts_transfers) {
            throw new AuthorizationException('This group does not accept transfers');
        }

        $advice = $this->route('advice');

        return $this->user()->can('transfer', $advice);
    }

    public function rules(): array
    {
        return [
            'group_id' => 'required|uuid|exists:groups,uuid',
            'reason' => 'nullable|string|max:1000',
        ];
    }
}
