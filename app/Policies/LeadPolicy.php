<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\Seller;

class LeadPolicy
{
    public function finish(Seller $user, Lead $lead): bool
    {
        if ((int) $user->brand_id !== (int) $lead->brand_id) {
            return false;
        }

        $role = $user->role ?? $user->is_seller;

        if ($role === 'front_seller') {
            return true;
        }

        if ($role === 'project_manager') {
            return LeadAssignment::where('lead_id', $lead->id)
                ->where('assigned_to', $user->id)
                ->exists();
        }

        return (int) $user->id === (int) $lead->seller_id;
    }

    public function view($user, Lead $lead): bool
    {
        if ($user instanceof Admin) {
            return ($user->role ?? 'admin') !== 'finance';
        }

        if ($user instanceof Seller) {
            $role = $user->role ?? $user->is_seller;

            if ($role === 'front_seller') {
                return (int) $user->brand_id === (int) $lead->brand_id;
            }

            if ($role === 'project_manager') {
                return LeadAssignment::where('lead_id', $lead->id)
                    ->where('assigned_to', $user->id)
                    ->exists();
            }

            return (int) $user->id === (int) $lead->seller_id;
        }

        return false;
    }

    /**
     * Admin, front seller in brand, or assigned PM may generate payment links.
     */
    public function createPaymentLink($user, Lead $lead): bool
    {
        if ($user instanceof Admin) {
            return ($user->role ?? 'admin') !== 'finance';
        }

        if ($user instanceof Seller) {
            if ((int) $user->brand_id !== (int) $lead->brand_id) {
                return false;
            }

            $role = $user->role ?? $user->is_seller;

            if ($role === 'front_seller') {
                return true;
            }

            if ($role === 'project_manager') {
                return LeadAssignment::where('lead_id', $lead->id)
                    ->where('assigned_to', $user->id)
                    ->exists();
            }
        }

        return false;
    }

    public function viewPerformance(?object $user, Seller $subject): bool
    {
        if (auth('admin')->check()) {
            return true;
        }

        if (auth('seller')->check()) {
            $viewer = auth('seller')->user();

            return (int) $viewer->id === (int) $subject->id;
        }

        return false;
    }
}
