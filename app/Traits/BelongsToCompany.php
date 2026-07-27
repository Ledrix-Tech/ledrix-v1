<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany()
    {
        static::addGlobalScope(new BelongsToCompanyScope);

        // Automatically set company_id when creating new record
        static::creating(function (Model $model) {
            $companyId = self::resolveCompanyId();
            if ($companyId && !$model->company_id) {
                $model->company_id = $companyId;
            }
        });
    }

    protected static function resolveCompanyId()
    {
        foreach (['admin', 'seller', 'client'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return auth()->guard($guard)->user()->company_id ?? null;
            }
        }
        return null;
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
