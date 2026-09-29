<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        return view('admin.login');
    }

    public function store(LoginRequest $request)
    {
        if (! Auth::attempt($request->only('username', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages(['username' => 'Tên đăng nhập hoặc mật khẩu không đúng.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('admin.home'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function home(Request $request)
    {
        foreach (array_keys(config('modules')) as $module) {
            if ($request->user()->hasModule($module)) {
                return redirect('/admin/'.$module);
            }
        } abort(403, 'Tài khoản chưa được cấp quyền.');
    }
}
