<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Order $order)
    {
        return $this->permission($user, $order);
    }

    public function create()
    {
        return true;
    }

    public function update(User $user, Order $order)
    {
        return $this->permission($user, $order);
    }

    public function delete(User $user, Order $order)
    {
        return $user->is_admin || $order->advisor_id === $user->id;
    }

    public function export(User $user)
    {
        return $user->is_admin;
    }

    public function addAdvisors(User $user, Order $order)
    {
        return $this->permission($user, $order);
    }

    private function permission(User $user, Order $order)
    {
        return $user->is_admin || $order->advisor_id === $user->id || $order->shares->contains($user->id);
    }
}
