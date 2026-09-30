{{--
    Form "Akun saya" (nama, email, password), dipakai panel admin & mitra.
    Variabel: $user, $routes ['update' => ..., 'password' => ...], $profileHint
--}}
    <div class="grid max-w-4xl gap-6 lg:grid-cols-2">
        {{-- Profil --}}
        <form action="{{ route($routes['update']) }}" method="POST" class="card space-y-4 p-5 lg:self-start">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-semibold text-stone-900">{{ __('Profile') }}</h2>
                <p class="text-sm text-stone-500">{{ $profileHint }}</p>
            </div>

            @if ($errors->profile->any())
                <div class="alert-error" role="alert">
                    @foreach ($errors->profile->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div>
                <label for="account-name" class="label">{{ __('Name') }}</label>
                <input type="text" name="name" id="account-name" value="{{ old('name', $user->name) }}" class="input" autocomplete="name" required maxlength="255">
            </div>
            <div>
                <label for="account-email" class="label">{{ __('Email') }}</label>
                <input type="email" name="email" id="account-email" value="{{ old('email', $user->email) }}" class="input" autocomplete="email" required maxlength="255">
            </div>
            <button type="submit" class="btn-primary">
                <i class="ti ti-device-floppy" aria-hidden="true"></i>{{ __('Save profile') }}
            </button>
        </form>

        {{-- Ganti password --}}
        <form action="{{ route($routes['password']) }}" method="POST" class="card space-y-4 p-5 lg:self-start">
            @csrf
            @method('PUT')
            <div>
                <h2 class="font-semibold text-stone-900">{{ __('Change password') }}</h2>
                <p class="text-sm text-stone-500">{{ __('Other devices signed in to this account will be logged out.') }}</p>
            </div>

            @if ($errors->password->any())
                <div class="alert-error" role="alert">
                    @foreach ($errors->password->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div>
                <label for="current-password" class="label">{{ __('Current password') }}</label>
                <input type="password" name="current_password" id="current-password" class="input" autocomplete="current-password" required>
            </div>
            <div>
                <label for="new-password" class="label">{{ __('New password') }}</label>
                <input type="password" name="password" id="new-password" class="input" autocomplete="new-password" minlength="8" required>
            </div>
            <div>
                <label for="new-password-confirmation" class="label">{{ __('Confirm password') }}</label>
                <input type="password" name="password_confirmation" id="new-password-confirmation" class="input" autocomplete="new-password" minlength="8" required>
            </div>
            <button type="submit" class="btn-primary">
                <i class="ti ti-lock" aria-hidden="true"></i>{{ __('Change password') }}
            </button>
        </form>
    </div>
