<?php

namespace Modules\User\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\User\Entities\ReferralCode;
use Modules\User\Entities\User;
use Modules\User\Entities\UserReferral;
use Modules\User\Http\UserResource;

class ReferralService
{
    private ReferralCode $model;

    public function __construct(ReferralCode $model)
    {
        $this->model = $model;
    }

    private function generateReferralCode(): string
    {
        return strtoupper(Str::random(6));
    }

    public function add($user_id)
    {
        $referral_code = $this->generateReferralCode();

        return handleTransaction(function () use ($user_id, $referral_code) {
            return $this->model->updateOrCreate(
                ['user_id' => $user_id],
                ['referral_code' => $referral_code]
            );
        }, 'Referral Code Successfully added', [], 200, true);
    }

    public function update($referral_code, $user_id = null)
    {
        return handleTransaction(function () use ($referral_code, $user_id) {
            $record = $this->model->where('referral_code', $referral_code)->first();

            $record?->increment('usage_count');

            if ($user_id) {
                UserReferral::updateOrCreate(
                    ['user_id' => $user_id],
                    ['referral_code' => $referral_code]
                );
            }

            return $record;
        }, 'Referral Code Successfully updated', [], 200, true);
    }

    public function checkCode($referral_code)
    {
        if (! $referral_code) {
            return false;
        }

        return $this->model
            ->where('referral_code', strtoupper($referral_code))
            ->exists();
    }

    public function baseReferralUserId($referral_code)
    {
        if (empty($referral_code)) {
            return false;
        }

        return $this->model
            ->whereRaw('UPPER(referral_code) = ?', [strtoupper($referral_code)])
            ->value('user_id') ?? false;
    }

    public function getAllUsersReferralDetails(Request $request)
    {
        $query = User::with('referralCode')->latest();
        $users = $request->has('page')
            ? $query->paginate(min(max((int) $request->input('per_page', $request->input('limit', 20)), 1), 100))
            : $query->get();

        $mapUser = function ($user) {
            $referral_code = $user->referralCode ? $user->referralCode->referral_code : null;
            $user->referral_code = $referral_code;
            $referredUsers = collect();
            if ($referral_code) {
                $referredUsers = UserReferral::with('user')
                    ->where('referral_code', $referral_code)
                    ->get()
                    ->pluck('user')
                    ->filter();
            }

            return [
                'user' => UserResource::make($user),
                'referred_users_count' => $referredUsers->count(),
                'referred_users' => UserResource::collection($referredUsers),
            ];
        };

        if ($users instanceof LengthAwarePaginator) {
            $users->setCollection($users->getCollection()->map($mapUser));
            $data = $users;
        } else {
            $data = $users->map($mapUser);
        }

        return responseHelper('All users referral details retrieved successfully', 200, $data);
    }

    public function getReferredUsers(int $userId)
    {
        $user = User::with('referralCode')->findOrFail($userId);

        $referralCode = $user->referralCode ? $user->referralCode->referral_code : null;

        if (! $referralCode) {
            return responseHelper('User has no referral code', 404);
        }

        $referredUsers = UserReferral::with('user')
            ->where('referral_code', $referralCode)
            ->get()
            ->pluck('user')
            ->filter();

        return responseHelper(
            'Referred users retrieved successfully',
            200,
            [
                'referred_users_count' => $referredUsers->count(),
                'referred_users' => UserResource::collection($referredUsers),
            ]
        );
    }
}
