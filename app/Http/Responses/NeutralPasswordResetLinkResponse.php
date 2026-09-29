<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;

class NeutralPasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse
{
    public function __construct(private readonly string $status) {}

    public function toResponse($request)
    {
        $message = trans('passwords.sent');

        return $request->wantsJson() ? response()->json(['message' => $message]) : back()->with('status', $message);
    }
}
