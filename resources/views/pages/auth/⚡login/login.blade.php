@php $title = 'Sign In'; @endphp

<div>
    <h2 class="text-xl font-semibold text-gray-900 mb-6">Sign in to your account</h2>

    <form wire:submit="login" class="space-y-5">

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
            <input wire:model="email" id="email" type="email" autocomplete="email" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm
                          focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none
                          @error('email') border-red-400 @enderror">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <a href="{{ route('auth.forgot-password') }}" wire:navigate
                    class="text-xs text-indigo-600 hover:text-indigo-500 font-medium">
                    Forgot password?
                </a>
            </div>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm
                          focus:border-indigo-500 focus:ring-indigo-500 focus:outline-none
                          @error('password') border-red-400 @enderror">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-2">
            <input wire:model="remember" id="remember" type="checkbox"
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <label for="remember" class="text-sm text-gray-600">Remember me</label>
        </div>

        <button type="submit" wire:loading.attr="disabled" class="w-full flex justify-center py-2.5 px-4 rounded-lg text-sm font-semibold
                       text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none
                       focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                       disabled:opacity-60 transition-colors">
            <span wire:loading.remove>Sign in</span>
            <span wire:loading>Signing in…</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        Don't have an account?
        <a href="{{ route('auth.register') }}" wire:navigate
            class="font-medium text-indigo-600 hover:text-indigo-500">Register</a>
    </p>
</div>