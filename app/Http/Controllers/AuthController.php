<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * แสดงฟอร์มสมัครสมาชิก
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * บันทึกสมาชิกใหม่ + ล็อกอินให้ทันที
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'กรุณากรอกชื่อ',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.email' => 'รูปแบบอีเมลไม่ถูกต้อง',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = $validated['password']; // ถูก hash อัตโนมัติจาก cast 'hashed'
        $user->role = 'user'; // สมาชิกใหม่เป็น user เสมอ
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('success', 'สมัครสมาชิกและเข้าสู่ระบบเรียบร้อยแล้ว');
    }

    /**
     * แสดงฟอร์มเข้าสู่ระบบ
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * ตรวจสอบและเข้าสู่ระบบ
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'กรุณากรอกอีเมล',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors(['email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended('/')->with('success', 'เข้าสู่ระบบเรียบร้อยแล้ว');
    }

    /**
     * ออกจากระบบ
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'ออกจากระบบเรียบร้อยแล้ว');
    }
}
