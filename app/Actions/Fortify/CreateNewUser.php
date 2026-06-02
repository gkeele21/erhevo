<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\UserCategoryService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        protected UserCategoryService $userCategoryService
    ) {}

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'show_lds_content' => ['nullable', 'boolean'],
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        $user = User::create([
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        // Persist the LDS content preference chosen at sign-up (opt-out default).
        $user->setSetting(
            'show_lds_content',
            filter_var($input['show_lds_content'] ?? true, FILTER_VALIDATE_BOOLEAN)
        )->save();

        $this->userCategoryService->copyDefaultsToUser($user);

        return $user;
    }
}
