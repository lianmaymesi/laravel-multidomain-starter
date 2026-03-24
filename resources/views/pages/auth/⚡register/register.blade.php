<div class="flex min-h-screen">
    <div class="flex-1 flex justify-center items-center">
        <div class="w-80 max-w-80 space-y-6">
            <div class="flex justify-center opacity-50">
                <a href="/" class="group flex items-center gap-3">
                    <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt=""
                        class="h-16 text-zinc-800 dark:text-white" />
                </a>
            </div>

            <flux:heading class="text-center" size="xl">Create an account</flux:heading>

            <div class="space-y-4">
                <flux:button class="w-full">
                    <x-slot name="icon">
                        <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M23.06 12.25C23.06 11.47 22.99 10.72 22.86 10H12.5V14.26H18.42C18.16 15.63 17.38 16.79 16.21 17.57V20.34H19.78C21.86 18.42 23.06 15.6 23.06 12.25Z"
                                fill="#4285F4" />
                            <path
                                d="M12.4997 23C15.4697 23 17.9597 22.02 19.7797 20.34L16.2097 17.57C15.2297 18.23 13.9797 18.63 12.4997 18.63C9.63969 18.63 7.20969 16.7 6.33969 14.1H2.67969V16.94C4.48969 20.53 8.19969 23 12.4997 23Z"
                                fill="#34A853" />
                            <path
                                d="M6.34 14.0899C6.12 13.4299 5.99 12.7299 5.99 11.9999C5.99 11.2699 6.12 10.5699 6.34 9.90995V7.06995H2.68C1.93 8.54995 1.5 10.2199 1.5 11.9999C1.5 13.7799 1.93 15.4499 2.68 16.9299L5.53 14.7099L6.34 14.0899Z"
                                fill="#FBBC05" />
                            <path
                                d="M12.4997 5.38C14.1197 5.38 15.5597 5.94 16.7097 7.02L19.8597 3.87C17.9497 2.09 15.4697 1 12.4997 1C8.19969 1 4.48969 3.47 2.67969 7.07L6.33969 9.91C7.20969 7.31 9.63969 5.38 12.4997 5.38Z"
                                fill="#EA4335" />
                        </svg>
                    </x-slot>

                    Continue with Google
                </flux:button>
            </div>

            <flux:separator text="or" />

            <form wire:submit="register" class="flex flex-col gap-4">
                <flux:input label="Full Name" type="text" placeholder="Peter Nelson" wire:model="name" />
                <flux:field>
                    <flux:label>Phone Number</flux:label>
                    <flux:input mask="99999-99999" placeholder="98765-43210" wire:model="phone">
                        <x-slot name="icon" class="text-sm!">
                            +91
                        </x-slot>
                    </flux:input>
                    <flux:error name="phone" />
                </flux:field>
                <flux:input label="Email" type="email" placeholder="Enter your email address" wire:model="email" />
                <flux:input label="Password" type="password" placeholder="Your password" wire:model="password" />
                <flux:input label="Confirm password" type="password" placeholder="Your password"
                    wire:model="password_confirmation" />
                <flux:button type="submit" variant="primary" class="w-full mt-4">Log in</flux:button>
            </form>

            <flux:subheading class="text-center">
                Already have an account? <flux:link href="{{ route('auth.login') }}">Sign in</flux:link>
            </flux:subheading>
        </div>
    </div>

    <div class="flex-1 p-4 max-lg:hidden">
        <div class="text-white relative rounded-lg h-full w-full bg-zinc-900 flex flex-col items-start justify-end p-16"
            style="background-image: url('/img/demo/auth_aurora_2x.png'); background-size: cover">
            <div class="flex gap-2 mb-4">
                <flux:icon.star variant="solid" />
                <flux:icon.star variant="solid" />
                <flux:icon.star variant="solid" />
                <flux:icon.star variant="solid" />
                <flux:icon.star variant="solid" />
            </div>

            <div class="mb-6 italic font-base text-3xl xl:text-4xl">
                Flux has enabled me to design, build, and deliver apps faster than ever before.
            </div>

            <div class="flex gap-4">
                <flux:avatar src="https://fluxui.dev/img/demo/caleb.png" size="xl" />

                <div class="flex flex-col justify-center font-medium">
                    <div class="text-lg">Caleb Porzio</div>
                    <div class="text-zinc-300">Creator of Livewire</div>
                </div>
            </div>
        </div>
    </div>
</div>