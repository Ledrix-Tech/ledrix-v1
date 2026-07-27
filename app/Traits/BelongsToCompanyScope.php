<?php


namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BelongsToCompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $companyId = $this->resolveCompanyId();

        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }

    protected function resolveCompanyId()
    {
        foreach (['admin', 'seller', 'client'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return auth()->guard($guard)->user()->company_id ?? null;
            }
        }
        return null;
    }
}
