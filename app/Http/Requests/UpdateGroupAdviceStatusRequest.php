<?php

namespace App\Http\Requests;

use App\Enums\AdviceStatusResult;
use App\Models\AdviceStatusGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateGroupAdviceStatusRequest extends FormRequest
{
    public function authorize()
    {
        if ($this->isOnlySettingVisibility()) {

            return $this->user()->can('update', $this->groupAdviceStatus());
        }

        return $this->user()->can('update', $this->route('advicestatus'));
    }

    public function groupAdviceStatus(): AdviceStatusGroup
    {
        $group = $this->route('group');
        $adviceStatus = $this->route('advicestatus');
        $pivot = $adviceStatus->usingGroups()->wherePivot('group_id', $group->id)->first();

        if ($pivot === null) {
            $pivot = new AdviceStatusGroup([
                'group_id' => $group->id,
                'advice_status_id' => $adviceStatus->id,
                'visible_in_group' => $this->visible_in_group,
            ]);
        }

        return $pivot;
    }

    public function rules()
    {
        return [
            'name' => 'string',
            'result' => new Enum(AdviceStatusResult::class),
            'visible_in_group' => 'boolean',
        ];
    }

    public function isOnlySettingVisibility()
    {
        $pivot = $this->groupAdviceStatus();
        $group = $this->route('group');
        if ($group->id !== $pivot->group_id) {
            return false;
        }

        return count($this->all()) === 1 && isset($this->visible_in_group);
    }
}
