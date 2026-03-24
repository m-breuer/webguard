<?php

declare(strict_types=1);

return [
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'register' => [
        'title' => 'Register',
        'description' => 'Create a new account to start monitoring your services.',
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'button' => 'Register',
        'login_button' => 'Back to Login',
        'already_registered' => 'Already registered?',
        'terms_agreement' => 'I accept the <a href=":terms_link" target="_blank" rel="noopener" class="underline hover:text-gray-900 dark:hover:text-gray-100">Terms of Use</a> and the <a href=":privacy_link" target="_blank" rel="noopener" class="underline hover:text-gray-900 dark:hover:text-gray-100">Privacy Policy</a>.',
    ],
    'login' => [
        'title' => 'Login',
        'description' => 'Access your account to manage your monitorings.',
        'demo_hint' => 'Demo credentials are prefilled. You can log in directly.',
        'email' => 'Email',
        'password' => 'Password',
        'remember' => 'Remember me',
        'forgot_password' => 'Forgot your password?',
        'button' => 'Login',
        'register_button' => 'Create Account',
        'demo_button' => 'Use Demo Credentials',
    ],
    'forgot_password' => [
        'title' => 'Forgot your password?',
        'description' => 'No problem. Just let us know your email address and we will email you a password reset link.',
        'button' => 'Email Password Reset Link',
    ],
    'confirm_password' => [
        'title' => 'Confirm Your Password',
        'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
    ],
    'reset_password' => [
        'title' => 'Reset Password',
        'email' => 'Email',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password',
        'button' => 'Reset Password',
    ],
    'verify_email' => [
        'heading' => 'Verify Your Email Address',
        'subheading' => 'Check Your Inbox',
        'description' => 'Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.',
        'link_sent' => 'A new verification link has been sent to the email address you provided during registration.',
        'resend_button' => 'Resend Verification Email',
    ],
    'logout' => 'Log Out',
    'or_continue_with' => 'or continue with',
    'github_login' => 'Sign in with GitHub',
    'auth_switch' => [
        'title' => 'Choose your access',
        'description' => 'Select what you want to do. The form updates on the right.',
        'login' => 'Login',
        'register' => 'Register',
        'demo' => 'Demo access',
    ],
    'github_consent' => [
        'title' => 'Legal Consent Required',
        'description' => 'Before continuing with GitHub, please confirm the Terms of Use and Privacy Policy.',
        'button' => 'Continue with GitHub',
        'cancel' => 'Cancel',
        'expired' => 'The GitHub login flow expired. Please start again.',
    ],
    'guest_login' => [
        'no_guest_user_found' => 'No guest user found.',
    ],
];
