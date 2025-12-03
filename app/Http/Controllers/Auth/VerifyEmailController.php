<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Class VerifyEmailController
 *
 * Handles the email verification process for registered users.
 */
class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  EmailVerificationRequest  $emailVerificationRequest  The email verification request instance.
     * @return RedirectResponse A redirect response after email verification.
     */
    public function __invoke(EmailVerificationRequest $emailVerificationRequest): RedirectResponse
    {
        if ($emailVerificationRequest->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($emailVerificationRequest->user()->markEmailAsVerified()) {
            event(new Verified($emailVerificationRequest->user()));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
