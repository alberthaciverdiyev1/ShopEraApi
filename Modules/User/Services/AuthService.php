<?php

namespace Modules\User\Services;

use App\Helpers\PhoneHelper;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Balance\Services\BalanceService;
use Modules\Notification\Services\NotificationTokenService;
use Modules\User\Emails\PasswordResetCodeMail;
use Modules\User\Entities\OtpEmail;
use Modules\User\Entities\PasswordResetRequest;
use Modules\User\Entities\User;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class AuthService
{
    private User $model;

    private NotificationTokenService $notificationTokenService;

    private ReferralService $referralService;

    private BalanceService $balanceService;

    private ReferralSettingService $referralSettingService;

    private OtpService $otpService;

    public function __construct(
        User $model,
        NotificationTokenService $notificationTokenService,
        ReferralService $referralService,
        BalanceService $balanceService,
        ReferralSettingService $referralSettingService,
        OtpService $otpService
    ) {
        $this->model = $model;
        $this->notificationTokenService = $notificationTokenService;
        $this->referralService = $referralService;
        $this->balanceService = $balanceService;
        $this->referralSettingService = $referralSettingService;
        $this->otpService = $otpService;
    }

    /**
     * Send OTP
     */
    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required',
        ]);

        $phone = PhoneHelper::normalize($validated['phone']);

        $key = 'send-otp:'.$phone;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return responseHelper(__('Too many OTP requests. Please try again after 3 minutes.'),
                429
            );
        }

        $otp = random_int(1000, 9999);
        $deactive_date = now()->addMinutes(10);

        try {
            OtpEmail::updateOrCreate(
                ['email' => $phone],
                [
                    'otp_code' => $otp,
                    'deactive_date' => $deactive_date,
                ]
            );

            $text = config('app.name').' OTP code: '.$otp;
            $this->otpService->sendSms($phone, $text);

            RateLimiter::hit($key, 180);

            return responseHelper(__('OTP sent successfully.'),
                StatusCode::HTTP_CREATED,
                [
                    'deactive_date' => $deactive_date,
                ]
            );

        } catch (Exception $exception) {
            return responseHelper(
                $exception->getMessage(),
                StatusCode::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function checkOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required',
            'otp' => 'required|digits:4',
        ]);

        $phone = PhoneHelper::normalize($validated['phone']);

        try {
            $userExists = PhoneHelper::wherePhone($this->model->newQuery(), $phone)->exists();

            // Yeni qeydiyyatdırsa OTP nə yazılsa keçsin
            if (! $userExists) {
                return responseHelper(__('OTP verified successfully.'), StatusCode::HTTP_OK);
            }

            // Mövcud userdirsə (məs: reset password), OTP normal yoxlansın
            $otp = $validated['otp'];

            $otpRecord = OtpEmail::where('email', $phone)
                ->where('otp_code', $otp)
                ->first();

            if (! $otpRecord) {
                return responseHelper(__('OTP not found or invalid.'), StatusCode::HTTP_NOT_FOUND);
            }

            if (now()->greaterThan($otpRecord->deactive_date)) {
                return responseHelper(__('OTP has expired.'), 403);
            }

            return responseHelper(__('OTP verified successfully.'), StatusCode::HTTP_OK);

        } catch (Exception $exception) {
            return responseHelper($exception->getMessage(), 403);
        }
    }

    /**
     * Register
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
            // Kept optional for backwards compatibility: old Flutter versions
            // still send it, while the new registration flow no longer uses OTP.
            'otpCode' => 'nullable|digits:4',
            'phone' => 'required|string|max:20',
        ]);

        $phone = PhoneHelper::normalize($validated['phone']);

        if ($phone === '') {
            return responseHelper(__('The phone field must contain a valid phone number.'), 422);
        }

        if (PhoneHelper::wherePhone($this->model->newQuery(), $phone)->exists()) {
            return responseHelper(__('This phone number is already in use.'), 422);
        }

        $email = $validated['email'] ?? null;

        try {
            $user = $this->model->create([
                'name' => Str::lower($validated['name'] ?? 'User'),
                'surname' => ! empty($validated['surname']) ? Str::lower($validated['surname']) : null,
                'phone' => $phone,
                'email' => $email,
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if (
                (string) $exception->getCode() === '23505'
                && str_contains($exception->getMessage(), 'Phone number already exists')
            ) {
                return responseHelper(__('This phone number is already in use.'), 422);
            }

            if (
                (string) $exception->getCode() === '23505'
                && str_contains($exception->getMessage(), 'email')
            ) {
                return responseHelper(__('This email address is already in use.'), 422);
            }

            throw $exception;
        }

        $user->assignRole('user');

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->referralService->add($user->id);

        return responseHelper(__('User registered successfully.'),
            200,
            [
                'token' => $token,
                'user' => $user->only(['id', 'name', 'email']),
            ]
        );
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            // Either identifier may be used; one of them is required.
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'password' => 'required|string|min:6',
            //  'device_token' => 'required|string',
            // 'device_type' => 'nullable|string'
        ]);

        if (empty($validated['phone']) && empty($validated['email'])) {
            return responseHelper(__('Phone or email is required.'), 422);
        }

        $query = $this->model->newQuery();

        if (! empty($validated['phone'])) {
            $phone = PhoneHelper::normalize($validated['phone']);
            $query = PhoneHelper::wherePhone($query, $phone);
        } else {
            // Older accounts may hold the address with capitals.
            $query->whereRaw('LOWER(email) = ?', [Str::lower(trim((string) $validated['email']))]);
        }

        $user = $query->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return responseHelper(__('Phone/email or password is incorrect.'), 403);
        }

        if (! $user->is_active) {
            return responseHelper(__('User is blocked'), StatusCode::HTTP_FORBIDDEN);
        }

        //        if (empty($user->email_verified_at)) {
        //            return responseHelper(__('Email address not verified.'), StatusCode::HTTP_FORBIDDEN);
        //        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        // $notificationTokenResponse = $this->notificationTokenService->updateOrCreate($validated, $user);

        return responseHelper(__('Login successful.'),
            StatusCode::HTTP_OK,
            [
                'token' => $token,
                'user' => $user->only(['id', 'name', 'email', 'phone']),
            ]
        );
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        if ($request->device_token) {
            $this->notificationTokenService->deleteToken($request);
        }

        return responseHelper(__('Logout successful.'), StatusCode::HTTP_OK);

    }

    /**
     * Reset Password
     */
    /**
     * Sends a four-digit code to an e-mail address so its owner can set a new
     * password without waiting for an SMS.
     *
     * The answer is the same whether the address is on an account or not: a
     * password-reset form should never be a way to find out who is registered.
     */
    public function sendPasswordResetEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $key = 'password-reset-email:'.$email;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return responseHelper(__('Too many OTP requests. Please try again after 3 minutes.'), 429);
        }

        RateLimiter::hit($key, 180);

        $user = $this->model->newQuery()->whereRaw('lower(email) = ?', [$email])->first();

        if ($user) {
            $otp = random_int(1000, 9999);
            $minutes = 10;

            OtpEmail::updateOrCreate(
                ['email' => $email],
                ['otp_code' => $otp, 'deactive_date' => now()->addMinutes($minutes)]
            );

            try {
                Mail::to($email)->send(new PasswordResetCodeMail((string) $otp, $minutes, $user->name));
            } catch (\Throwable $exception) {
                // The address exists but the mail did not leave. Saying so is
                // fair here: nothing is revealed that the sender did not type.
                \Log::error('Password reset e-mail could not be sent.', [
                    'error' => $exception->getMessage(),
                ]);

                return responseHelper(__('The message could not be sent. Please try again later.'), 500);
            }
        }

        return responseHelper(__('If the address belongs to an account, the code is on its way.'), StatusCode::HTTP_OK);
    }

    /** Sets the new password against the code that was e-mailed. */
    public function resetPasswordByEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'otpCode' => ['required', 'digits:4'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $email = mb_strtolower(trim($validated['email']));

        $otp = OtpEmail::where('email', $email)
            ->where('otp_code', $validated['otpCode'])
            ->where('deactive_date', '>', now())
            ->first();

        if (! $otp) {
            return responseHelper(__('OTP code is invalid or expired.'), StatusCode::HTTP_FORBIDDEN);
        }

        $user = $this->model->newQuery()->whereRaw('lower(email) = ?', [$email])->first();

        if (! $user) {
            return responseHelper(__('OTP code is invalid or expired.'), StatusCode::HTTP_FORBIDDEN);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        $otp->delete();
        // Every other phone is signed out, as the phone flow already does.
        $user->tokens()->delete();

        return responseHelper(
            __('Password reset successfully.'),
            StatusCode::HTTP_OK,
            [
                'token' => $user->createToken('auth_token')->plainTextToken,
                'user' => $user->only(['id', 'name', 'email']),
            ]
        );
    }

    public function resetPassword(Request $request)
    {

        $validated = $request->validate([
            // 'phone' => 'required|exists:users,phone',
            'phone' => 'required',
            'otpCode' => 'required|digits:4',
            'password' => 'required|string|min:6|confirmed',
        ]);
        $validated['phone'] = PhoneHelper::normalize($validated['phone']);
        $otpCheck = OtpEmail::where([
            'email' => $validated['phone'],
            'otp_code' => $validated['otpCode'],
        ])->where('deactive_date', '>', now())->first();

        if (! $otpCheck) {
            return responseHelper(__('OTP code is invalid or expired.'), StatusCode::HTTP_FORBIDDEN);
        }

        $user = PhoneHelper::wherePhone($this->model->newQuery(), $validated['phone'])->first();
        $user->password = Hash::make($validated['password']);
        $user->save();

        $otpCheck->delete();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return responseHelper(__('Password reset successfully.'),
            StatusCode::HTTP_OK,
            [
                'token' => $token,
                'user' => $user->only(['id', 'name', 'email']),
            ]
        );
    }

    public function createPasswordResetRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $phone = PhoneHelper::normalize($validated['phone']);
        $user = PhoneHelper::wherePhone($this->model->newQuery(), $phone)->where('is_active', true)->first();

        if ($user) {
            PasswordResetRequest::firstOrCreate(
                ['user_id' => $user->id, 'status' => 'pending'],
                ['phone' => $phone, 'note' => $validated['note'] ?? null]
            );
        }

        return responseHelper(__('Şifrə sıfırlama istəyiniz adminə göndərildi.'), 202);
    }

    public function passwordResetRequests(Request $request): JsonResponse
    {
        $query = PasswordResetRequest::with('user:id,name,surname,phone,email')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn ($q) => $q->where('phone', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")));
        }

        return responseHelper(__('Password reset requests retrieved successfully.'), 200,
            $query->paginate(min(max((int) $request->input('per_page', 20), 1), 100)));
    }

    public function resolvePasswordResetRequest(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $resetRequest = PasswordResetRequest::where('status', 'pending')->findOrFail($id);
        DB::transaction(function () use ($resetRequest, $validated, $request) {
            $resetRequest->user->update(['password' => Hash::make($validated['password'])]);
            $resetRequest->user->tokens()->delete();
            $resetRequest->update([
                'status' => 'resolved', 'resolved_by' => $request->user()->id, 'resolved_at' => now(),
            ]);
        });

        return responseHelper(__('Password reset request resolved successfully.'), 200, $resetRequest->fresh('user'));
    }

    public function dismissPasswordResetRequest(Request $request, int $id): JsonResponse
    {
        $resetRequest = PasswordResetRequest::where('status', 'pending')->findOrFail($id);
        $resetRequest->update([
            'status' => 'dismissed', 'resolved_by' => $request->user()->id, 'resolved_at' => now(),
        ]);

        return responseHelper(__('Password reset request dismissed.'), 200, $resetRequest);
    }

    /**
     * Change Password
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return responseHelper(__('Current password is incorrect.'), 403);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return responseHelper(__('Password changed successfully.'),
            StatusCode::HTTP_OK,
            [
                'token' => $token,
                'user' => $user->only(['id', 'name', 'email']),
            ]
        );
    }

    public function adminChangePassword(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $this->model->findOrFail($validated['user_id']);
        if (! $user) {
            return responseHelper(__('User not found.'), 404);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        $user->tokens()->delete();

        return responseHelper(__('Password changed successfully.'));
    }
}
