<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Context\GroupContextContract;
use App\Models\Group;

trait RequiresCurrentGroup
{
    /**
     * Imports, exports and spreadsheet mappings work on the map points of one group. System admins
     * pass the policies without a selected group, so they are stopped here.
     */
    protected function currentGroup(GroupContextContract $groupContext): Group
    {
        return $groupContext->getCurrentGroup() ?? abort(403, 'Bitte wähle zuerst eine Initiative aus.');
    }
}
