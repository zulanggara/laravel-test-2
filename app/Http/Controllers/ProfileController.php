<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        return view('auth.profile');
    }

    public function update(ProfileUpdateRequest $request)
    {
        // Task: fill in the code here to update name and email
        // Also, update the password if it is set
        $request->validated();
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password_confirmation' => Hash::make($request->password_confirmation) ?? NULL
        ];
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }
        Auth::user()->update($data);

        return redirect()->route('profile.show')->with('success', 'Profile updated.');
    }
}
