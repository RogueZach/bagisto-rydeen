<?php

namespace Rydeen\Dealer\Http\Traits;

trait ScopesForRep
{
    /**
     * Check if the current admin user has the "Sales Rep" role.
     */
    protected function isRep(): bool
    {
        $admin = auth('admin')->user();

        return $admin && $admin->role && $admin->role->name === 'Sales Rep';
    }

    /**
     * Return the current admin's ID if they are a rep, null otherwise.
     */
    protected function repId(): ?int
    {
        return $this->isRep() ? auth('admin')->id() : null;
    }
}
