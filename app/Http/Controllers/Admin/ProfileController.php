<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** Lets the signed-in admin change their own password. */
class ProfileController extends AdminController
{
    protected string $title = 'Şifrəni dəyiş';

    public function edit()
    {
        return view('admin.pages.profile', ['title' => $this->title]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = admin_user();

        if (! $user || ! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => __('Cari şifrə yanlışdır.')]);
        }

        // The User model casts password as "hashed", so plain text is hashed.
        $user->update(['password' => $data['password']]);

        return back()->with('status', __('Şifrə yeniləndi.'));
    }
}
