<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FormSubmission;
use App\Models\User;
use App\Policies\Concerns\GroupContextHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

class FormSubmissionPolicy
{
    use GroupContextHelper;
    use HandlesAuthorization;

    /**
     * Members of the submission's group see its submissions. Advisors who may see the protected data of the
     * advice created from it see the submission as well.
     */
    public function view(User $user, FormSubmission $formSubmission): bool
    {
        if ($formSubmission->group !== null && $this->groupContext->isActingAsTransitiveMemberOrAdmin($user, $formSubmission->group)) {
            return true;
        }

        return $formSubmission->advice !== null && $user->can('viewDataProtected', $formSubmission->advice);
    }
}
