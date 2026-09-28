<x-layout>
    <div class="w-full">
        <div class="mb-6 rounded-md border border-gray-300 bg-white p-4 sm:p-8">
            <div class="max-w-xl">
                @include ('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="mb-6 rounded-md border border-gray-300 bg-white p-4 sm:p-8">
            <div class="max-w-xl">
                @include ('profile.partials.update-password-form')
            </div>
        </div>

        <div class="rounded-md border border-gray-300 bg-white p-4 sm:p-8">
            <div class="max-w-xl">
                @include ('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-layout>
