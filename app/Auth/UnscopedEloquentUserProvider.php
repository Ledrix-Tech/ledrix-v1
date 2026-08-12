<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

/**
 * Eloquent user provider that ignores BelongsToTenant global scopes.
 *
 * Without this, a stale session tenant_id can scope Auth::user() lookups so the
 * admin/seller row is invisible → intermittent “workspace not available” after login.
 */
class UnscopedEloquentUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier)
    {
        $model = $this->createModel();

        return $this->newModelQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->first();
    }

    public function retrieveByToken($identifier, $token)
    {
        $model = $this->createModel();

        $retrievedModel = $this->newModelQuery($model)->where(
            $model->getAuthIdentifierName(),
            $identifier
        )->first();

        if (! $retrievedModel) {
            return null;
        }

        $rememberToken = $retrievedModel->getRememberToken();

        return $rememberToken && hash_equals($rememberToken, $token)
            ? $retrievedModel
            : null;
    }

    public function retrieveByCredentials(array $credentials)
    {
        $credentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains($key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if (count($credentials) === 0) {
            return null;
        }

        $query = $this->newModelQuery($this->createModel());

        foreach ($credentials as $key => $value) {
            if (is_array($value) || $value instanceof \Illuminate\Contracts\Database\Query\Expression) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    protected function newModelQuery($model = null)
    {
        $model ??= $this->createModel();

        return $model->newQueryWithoutScopes();
    }
}
