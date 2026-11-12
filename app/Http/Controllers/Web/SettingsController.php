<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\UpdateProfileRequest;
use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\User\Http\Controllers\UserController;

class SettingsController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(): View
    {
        return view('storefront.settings.index', [
            'user' => request()->user(),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $user = $request->user();

            foreach (['name' => 'changeName', 'surname' => 'changeSurname', 'email' => 'changeEmail'] as $field => $method) {
                $value = isset($data[$field]) ? trim((string) $data[$field]) : '';

                if ($value === '' || $value === (string) $user->{$field}) {
                    continue;
                }

                app(UserController::class)->{$method}(
                    $this->moduleHttpRequest([$field => $value], 'PUT')
                );
            }
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

        app(UserController::class)->changeAvatar($request);

        return back()->with('status', 'Profil şəkli yeniləndi.');
    }

    public function removeAvatar(): RedirectResponse
    {
        app(UserController::class)->removeAvatar($this->moduleHttpRequest([], 'DELETE'));

        return back()->with('status', 'Profil şəkli silindi.');
    }
}
