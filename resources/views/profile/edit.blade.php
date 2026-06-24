<x-app-layout wide>
    <x-slot name="header">
        <h1 class="font-display font-medium text-[26px] tracking-wide text-vellum">Profile</h1>
    </x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="panel">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="panel">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="panel">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
