<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // برای API هیچوقت ریدایرکت نکن
        return $request->expectsJson() ? null : null;
    }
}
