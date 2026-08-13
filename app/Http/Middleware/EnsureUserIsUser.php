<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsUser
{
    /**
     * อนุญาตเฉพาะสมาชิกทั่วไป (role = user) เท่านั้น
     * ใช้กับฟีเจอร์ฝั่งผู้ใช้ เช่น การจองตั๋ว — admin เข้าไม่ได้
     * (ใช้คู่กับ 'auth' เพื่อให้ guest ถูกส่งไป login ก่อน)
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== 'user') {
            abort(403, 'เฉพาะสมาชิกเท่านั้น (สำหรับผู้ใช้ทั่วไป)');
        }

        return $next($request);
    }
}
