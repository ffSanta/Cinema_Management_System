<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * อนุญาตเฉพาะผู้ใช้ที่ล็อกอินและมี role = admin เท่านั้น
     * (ต้องใช้คู่กับ middleware 'auth' เพื่อให้ guest ถูกส่งไปหน้า login ก่อน)
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== 'admin') {
            abort(403, 'เฉพาะผู้ดูแลระบบเท่านั้น');
        }

        return $next($request);
    }
}
