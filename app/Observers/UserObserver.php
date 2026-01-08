<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Package;
use App\Models\User;
use Exception;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        if ($user->package_id === null) {
            $cheapestSelectablePackage = Package::cheapest();
            if ($cheapestSelectablePackage) {
                $user->package_id = $cheapestSelectablePackage->id;
            } else {
                // Fallback: Assign the first available package if no selectable free package is found.
                // This might assign a non-selectable package, which might not be ideal for the registration flow.
                $fallbackPackage = Package::query()->first();
                if ($fallbackPackage) {
                    $user->package_id = $fallbackPackage->id;
                } else {
                    // Critical error: No packages available at all.
                    throw new Exception('No packages available to assign to new user.');
                }
            }
        }
    }
}
