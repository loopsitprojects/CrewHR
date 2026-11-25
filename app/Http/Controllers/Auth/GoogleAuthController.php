<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle()
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('login')->with(
                'error',
                'Google Login is not configured yet. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env file.'
            );
        }

        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');

        // If specific work email domain(s) configured, pass the first one as hosted domain (hd) hint to Google
        $allowedDomains = $this->getAllowedDomains();
        if (!empty($allowedDomains)) {
            $driver->with(['hd' => $allowedDomains[0]]);
        }

        return $driver->redirect();
    }

    /**
     * Obtain the user information from Google and authenticate.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('login')->with(
                'error',
                'Google authentication was cancelled: ' . $request->get('error')
            );
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('login')->with(
                'error',
                'Unable to authenticate with Google: ' . $e->getMessage()
            );
        }

        $email = strtolower(trim($googleUser->getEmail() ?? ''));

        if (empty($email)) {
            return redirect()->route('login')->with('error', 'Google did not provide an email address for your account.');
        }

        // Domain verification for work email
        $allowedDomains = $this->getAllowedDomains();
        $emailDomain = substr(strrchr($email, "@"), 1);

        if (!empty($allowedDomains) && !in_array($emailDomain, $allowedDomains, true)) {
            $allowedListStr = implode(', ', array_map(fn($d) => '@' . $d, $allowedDomains));
            return redirect()->route('login')->with(
                'error',
                "Access restricted: '{$email}' is not an authorized work email domain. Please sign in with your company account ({$allowedListStr})."
            );
        }

        // 1. Try to find user by Google ID
        $user = User::where('google_id', $googleUser->getId())->first();

        // 2. Try to find user by email
        if (!$user) {
            $user = User::where('email', $email)->first();
        }

        // 3. Try to find via employee company_email or personal_email
        if (!$user) {
            $employee = Employee::where('company_email', $email)
                ->orWhere('personal_email', $email)
                ->first();

            if ($employee && $employee->user_id) {
                $user = User::find($employee->user_id);
            }
        }

        // 4. If user not found, handle auto-provisioning
        if (!$user) {
            $autoRegister = config('services.google.auto_register', true);

            if (!$autoRegister) {
                return redirect()->route('login')->with(
                    'error',
                    "No employee profile found for '{$email}'. Please contact HR to register your work account before logging in."
                );
            }

            // Generate unique username from email
            $baseUsername = explode('@', $email)[0];
            $username = $baseUsername;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            $user = User::create([
                'name' => $googleUser->getName() ?: ucwords(str_replace(['.', '_', '-'], ' ', $baseUsername)),
                'username' => $username,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
            ]);

            // Auto-create Employee profile if not linked
            if (!$user->employee) {
                $defaultDept = Department::first();
                $defaultDesig = Designation::first();

                Employee::create([
                    'user_id' => $user->id,
                    'employee_id_number' => 'EMP-' . strtoupper(Str::random(4)) . rand(100, 999),
                    'company_email' => $email,
                    'department_id' => $defaultDept?->id ?? 1,
                    'designation_id' => $defaultDesig?->id ?? 1,
                    'system_role' => 'Employee',
                    'joined_date' => now()->toDateString(),
                ]);
            }
        } else {
            // Update Google ID and avatar if needed
            $updates = [];
            if ($user->google_id !== $googleUser->getId()) {
                $updates['google_id'] = $googleUser->getId();
            }
            if (empty($user->avatar) && $googleUser->getAvatar()) {
                $updates['avatar'] = $googleUser->getAvatar();
            }
            if (!empty($updates)) {
                $user->update($updates);
            }
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Get list of allowed domains from config.
     *
     * @return array<string>
     */
    protected function getAllowedDomains(): array
    {
        $raw = config('services.google.allowed_domains', '');
        if (empty($raw)) {
            return [];
        }

        if (is_array($raw)) {
            return array_map('strtolower', array_map('trim', $raw));
        }

        $items = explode(',', $raw);
        $trimmed = array_map('trim', $items);
        $lowercased = array_map('strtolower', $trimmed);
        return array_values(array_filter($lowercased));
    }
}
