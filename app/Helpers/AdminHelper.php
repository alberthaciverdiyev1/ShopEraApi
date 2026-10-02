<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('admin_user')) {
    /**
     * The currently signed-in admin (marketplace user), if any.
     */
    function admin_user(): ?\Modules\User\Entities\User
    {
        return Auth::guard('admin')->user();
    }
}

if (! function_exists('admin_has_role')) {
    /**
     * Roles that are allowed into the panel without an explicit permission.
     */
    function admin_has_role(?\Modules\User\Entities\User $user = null): bool
    {
        $user ??= admin_user();

        if (! $user) {
            return false;
        }

        try {
            return $user->hasAnyRole(['admin', 'developer', 'manager', 'super-admin']);
        } catch (\Throwable) {
            return false;
        }
    }
}

if (! function_exists('admin_can')) {
    /**
     * Permission check that is independent of the auth guard: spatie resolves
     * the guard from the User model itself (sanctum). Admins/managers bypass.
     */
    function admin_can(string $permission, ?\Modules\User\Entities\User $user = null): bool
    {
        $user ??= admin_user();

        if (! $user) {
            return false;
        }

        if (admin_has_role($user)) {
            return true;
        }

        try {
            return $user->hasPermissionTo($permission);
        } catch (\Throwable) {
            return false;
        }
    }
}

if (! function_exists('admin_can_any')) {
    function admin_can_any(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (admin_can($permission)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('admin_label')) {
    /**
     * Human label for a translatable/plain attribute. spatie's accessor returns
     * the *current locale string*, not an array, so option builders must go
     * through getTranslations() to read the `az` value.
     */
    function admin_label(?\Illuminate\Database\Eloquent\Model $model, string $field = 'name', ?string $fallback = null): string
    {
        if (! $model) {
            return $fallback ?? '';
        }

        $value = method_exists($model, 'getTranslations')
            ? ($model->getTranslations($field)['az'] ?? null)
            : $model->{$field};

        if (is_array($value)) {
            $value = $value['az'] ?? reset($value);
        }

        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : ($fallback ?? ('#'.$model->getKey()));
    }
}
