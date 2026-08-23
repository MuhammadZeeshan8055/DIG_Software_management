<x-guest-layout>
    <div class="login-page">
        <div class="container">
            <div class="row login-row">
                <div class="col-12 col-sm-8 col-md-6 col-lg-4">

                    <div class="login-card">
                        <div class="login-card__brand">
                            <img
                                src="{{ asset('images/logo-icon.png') }}"
                                alt="DHOTHAR"
                                class="login-card__logo-icon"
                            >
                            <div class="login-card__brand-text">
                                <span class="brand-line brand-line--blue">Dhothar</span>
                                <span class="brand-line brand-line--yellow">International Group</span>
                                <span class="brand-line brand-line--tagline">travel and tour</span>
                            </div>
                        </div>

                        <div class="login-card__heading">
                            <h1 class="login-card__title">Employee Sign In</h1>
                            <p class="login-card__subtitle">
                                Secure access for authorized team members
                            </p>
                        </div>

                        @if (session('status'))
                            <div class="login-status">{{ session('status') }}</div>
                        @endif

                        <form class="login-form" method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="login-field">
                                <label for="email">Email</label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="Enter your work email"
                                >
                                @error('email')
                                    <p class="login-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="login-field">
                                <label for="password">Password</label>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                >
                                @error('password')
                                    <p class="login-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="btn-login">Login</button>
                        </form>

                        <div class="login-links">
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}">Forgot Password?</a>
                            @endif
                            @if (Route::has('register'))
                                <span class="login-links__dot">·</span>
                                <a href="{{ route('register') }}">Create Employee Account</a>
                            @endif
                        </div>

                        <p class="login-card__footer">
                            Access managed by company administrator only.
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
