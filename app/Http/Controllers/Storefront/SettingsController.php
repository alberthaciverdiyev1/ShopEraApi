<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\UpdateProfileRequest;
use App\Services\Storefront\AccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private readonly AccountService $account) {}

    public function index(): View
    {
        return view('storefront.settings.index', [
            'user' => request()->user(),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        try {
            $this->account->updateProfile($request->validated());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Profil məlumatları yeniləndi.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'avatar.required' => 'Şəkil seçilməlidir.',
            'avatar.image' => 'Yalnız şəkil yükləmək olar.',
            'avatar.mimes' => 'Yalnız JPG, PNG və WEBP formatları qəbul edilir.',
            'avatar.max' => 'Şəkilin ölçüsü 5MB-dan çox ola bilməz.',
        ]);

        $this->account->uploadAvatar($request);

        return back()->with('status', 'Profil şəkli yeniləndi.');
    }

    public function removeAvatar(): RedirectResponse
    {
        $this->account->removeAvatar();

        return back()->with('status', 'Profil şəkli silindi.');
    }
}
