<?php

namespace Modules\User\Services;

use App\Helpers\DeleteAccountHtml;
use App\Helpers\PhoneHelper;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Order\Entities\Order;
use Modules\User\Entities\OtpEmail;
use Modules\User\Entities\User;
use Modules\User\Http\UserResource;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class UserService
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * The account being edited: the caller's own, or another one for admins.
     */
    private function targetUser(Request $request, ?int $userId): ?User
    {
        if (! $userId) {
            return $request->user();
        }

        if ($userId !== $request->user()?->id) {
            abort_unless(
                $request->user()?->can('update user'),
                403,
                'User does not have the right permissions.'
            );
        }

        return $this->user->find($userId);
    }

    public function changeEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|unique:users,email',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $validated['user_id'] ?? null;
        $user = $this->targetUser($request, $userId);

        if (! $user) {
            return responseHelper(__('User not found or not authenticated'), 404);
        }

        return handleTransaction(function () use ($user, $validated) {
            $user->email = $validated['email'];
            $user->save();

            return $user;
        }, 'Email changed successfully');
    }

    public function changeName(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $validated['user_id'] ?? null;
        $user = $this->targetUser($request, $userId);

        if (! $user) {
            return responseHelper(__('User not found or not authenticated'), 404);
        }

        return handleTransaction(function () use ($user, $validated) {
            $user->name = strtolower($validated['name']);
            $user->save();

            return $user;
        }, 'Name changed successfully');
    }

    public function changePhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:255',
            'otpCode' => 'required|digits:4',
        ]);
        $validated['phone'] = PhoneHelper::normalize($validated['phone']);
        $user = $request->user();

        if ($validated['phone'] === '') {
            return responseHelper(__('The phone field must contain a valid phone number.'), 422);
        }

        if (PhoneHelper::wherePhone($this->user->newQuery(), $validated['phone'])
            ->where('id', '!=', $user->id)
            ->exists()) {
            return responseHelper(__('This phone number is already in use.'), 422);
        }

        $otpCheck = OtpEmail::where([
            'email' => $validated['phone'],
            'otp_code' => $validated['otpCode'],
        ])->where('deactive_date', '>', now())->first();

        if (! $otpCheck) {
            return response()->json([
                'status' => StatusCode::HTTP_FORBIDDEN,
                'message' => 'OTP code is invalid or expired',
            ], StatusCode::HTTP_FORBIDDEN);
        }

        try {
            $updatedUser = DB::transaction(function () use ($user, $validated, $otpCheck) {
                $user->phone = $validated['phone'];
                $user->save();
                $otpCheck->delete();

                return $user;
            });

            return responseHelper(__('Phone changed successfully'), 200, $updatedUser);
        } catch (QueryException $exception) {
            if (
                (string) $exception->getCode() === '23505'
                && str_contains($exception->getMessage(), 'Phone number already exists')
            ) {
                return responseHelper(__('This phone number is already in use.'), 422);
            }

            Log::error($exception->getMessage());

            return responseHelper(__('Operation failed.'), 403);
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage());

            return responseHelper(__('Operation failed.'), 403);
        }
    }

    public function changeSurname(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'surname' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = $validated['user_id'] ?? null;
        $user = $this->targetUser($request, $userId);

        if (! $user) {
            return responseHelper(__('User not found or not authenticated'), 404);
        }

        return handleTransaction(function () use ($user, $validated) {
            $user->surname = strtolower($validated['surname']);
            $user->save();

            return $user;
        }, 'Surname changed successfully');
    }

    public function changeAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $user = $request->user();
        if (! $user) {
            return responseHelper(__('User not found or not authenticated'), 404);
        }

        $file = $request->file('avatar');
        $fileName = 'avatar_'.$user->id.'_'.time().'.'.$file->getClientOriginalExtension();

        $directory = TenantContext::storagePath('avatars');

        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0755, true);
        }

        $oldPath = $user->getRawOriginal('avatar');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $file->storeAs($directory, $fileName, 'public');
        $user->avatar = "{$directory}/{$fileName}";
        $user->save();

        return responseHelper(__('Avatar updated successfully.'), 200, UserResource::make($user));
    }

    public function removeAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return responseHelper(__('User not found or not authenticated'), 404);
        }

        $oldPath = $user->getRawOriginal('avatar');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $user->avatar = null;
        $user->save();

        return responseHelper(__('Avatar removed successfully.'), 200, UserResource::make($user));
    }

    public function getAll(Request $request): JsonResponse
    {
        $query = User::with(['balance', 'roles']);

        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere(function ($phoneQuery) use ($search) {
                        PhoneHelper::wherePhone($phoneQuery, $search);
                    });
            });
        }

        if ($request->boolean('only_team')) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', '!=', 'user');
            });
        }

        if ($request->filled('role') && ! $request->boolean('only_team')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $dynamicCount = $query->count();
        $perPage = min(max((int) $request->input('limit', 20), 1), 100);
        $users = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'count' => $dynamicCount,
            'user' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function blockUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'block' => 'required|boolean',
        ]);

        $user = $this->user->find($validated['user_id']);

        if ($user->id === auth()->id()) {
            return responseHelper(__('You cannot block your own account'), 403);
        }

        if ($user->is_active !== $validated['block']) {
            return responseHelper(
                $validated['block'] ? __('User is already blocked.') : __('User is already unblocked.'),
                403
            );
        }

        if (! $user) {
            return responseHelper(__('User not found'), 404);
        }

        return handleTransaction(function () use ($user, $validated) {
            $user->is_active = ! $validated['block'];
            $user->save();

            if ($validated['block']) {
                $user->tokens()->delete();
            }

            return $user;
        }, 'User '.($validated['block'] ? 'blocked' : 'unblocked').' successfully');
    }

    public function changeWholesalerStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'is_wholesaler' => ['required', 'boolean'],
        ]);

        $user = $this->user->find($validated['user_id']);

        if (! $user) {
            return responseHelper(__('User not found'), 404);
        }

        return handleTransaction(function () use ($user, $validated) {
            $user->is_wholesaler = $validated['is_wholesaler'];
            $user->save();

            return $user->refresh();
        }, 'Wholesale status updated successfully', UserResource::class);
    }

    public function details(?int $id = null): JsonResponse
    {
        $self = Auth::user();

        // Another account may only be inspected with the right permission.
        if ($id && $id !== $self?->id) {
            abort_unless($self?->can('details user'), 403, 'User does not have the right permissions.');
        }

        $user = $id
            ? User::with(['roles.permissions', 'referralCode'])->find($id)
            : $self?->load(['roles.permissions', 'referralCode']);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->referralCode) {
            $user->referral_code = $user->referralCode->referral_code;
        } else {
            $user->referral_code = null;
        }

        $roles = $user->roles->map(function ($role) {
            return [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ];
        });

        return response()->json([
            'success' => true,
            'user' => UserResource::make($user),
            'roles' => $roles,
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        if ($id === auth()->id()) {
            return responseHelper(__('You cannot delete your own account'), 403);
        }

        $user = $this->user->find($id);

        if (! $user) {
            return responseHelper(__('User not found'), 404);
        }

        return handleTransaction(function () use ($user) {
            $user->forceDelete();

            return $user;
        }, 'User deleted successfully');
    }

    public function deleteMyAccount(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return responseHelper(__('User not authenticated'), 403);
        }

        try {
            $user->tokens()->delete();

            $user->delete();

            return responseHelper(__('User deleted successfully'), 200);

        } catch (\Exception $e) {
            \Log::error('Error deleting user: '.$e->getMessage());

            return responseHelper(__('Failed to delete user'), 403);
        }
    }

    public function deleteMyAccountHtml()
    {
        return (new DeleteAccountHtml)();
    }

    /** Team members: users holding any non-customer role. */
    public function teamQuery(Request $request): Builder
    {
        $query = $this->user->newQuery()
            ->with('roles')
            ->whereHas('roles', fn ($q) => $q->where('name', '!=', 'user'))
            ->latest('id');

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('surname', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        return $query;
    }

    /**
     * Admin user list with the search + role filters used by the panel.
     */
    public function adminQuery(Request $request): Builder
    {
        $query = $this->user->newQuery()->with('roles')->latest('id');

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('surname', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->query('role')));
        }

        return $query;
    }

    /**
     * User shown on the admin detail page with everything the view needs.
     *
     * @return array{user: User, recentOrders: Collection, ordersCount: int}
     */
    public function adminDetails(int $id): array
    {
        $user = $this->user->newQuery()
            ->with(['roles', 'permissions', 'balance'])
            ->findOrFail($id);

        return [
            'user' => $user,
            'recentOrders' => Order::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'ordersCount' => Order::query()->where('user_id', $user->id)->count(),
        ];
    }

    /** Role names available on the given guard, for the filter/select boxes. */
    public function roleNames(string $guard): Collection
    {
        return Role::query()->where('guard_name', $guard)->orderBy('name')->pluck('name');
    }

    /** Prevents an admin from blocking or deleting their own account. */
    private function forbidSelf(int $id, string $message): void
    {
        abort_if($id === Auth::guard('admin')->id(), 403, $message);
    }

    public function adminSetActive(int $id, bool $active): void
    {
        $this->forbidSelf($id, 'Öz hesabınızı bloklaya bilməzsiniz.');

        $this->user->newQuery()->findOrFail($id)->update(['is_active' => $active]);
    }

    public function adminSyncRoles(int $id, array $roles): void
    {
        $this->user->newQuery()->findOrFail($id)->syncRoles($roles);
    }

    public function adminChangePassword(int $id, string $password): void
    {
        $this->user->newQuery()->findOrFail($id)->update(['password' => Hash::make($password)]);
    }

    public function adminDelete(int $id): void
    {
        $this->forbidSelf($id, 'Öz hesabınızı silə bilməzsiniz.');

        $user = $this->user->newQuery()->findOrFail($id);

        // Admin hesabları (özü də daxil) panelə girişi itirməsin deyə silinmir.
        abort_if(admin_has_role($user), 403, 'Admin hesabları silinə bilməz.');

        $user->delete();
    }
}
