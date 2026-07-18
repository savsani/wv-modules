<x-ui.card-section :title="__('Profile Information')" :description="__('Update your account\'s name and email address.')">
    <form method="POST" action="{{ route('user-profile-information.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <x-form.field label="Name" for="name" required :error="$errors->updateProfileInformation->first('name')">
            <x-form.input id="name" name="name" :value="old('name', auth()->user()->name)" required autofocus autocomplete="name" :error="$errors->updateProfileInformation->has('name')" />
        </x-form.field>

        <x-form.field label="Email" for="email" required :error="$errors->updateProfileInformation->first('email')">
            <x-form.input id="email" type="email" name="email" :value="old('email', auth()->user()->email)" required autocomplete="username" :error="$errors->updateProfileInformation->has('email')" />
        </x-form.field>

        <x-ui.button type="submit" variant="primary" style="solid">Save</x-ui.button>

        @if(session('status') === \Laravel\Fortify\Fortify::PROFILE_INFORMATION_UPDATED)
            <div x-init="$store.toast.show({ message: 'Profile information updated.', type: 'success' })"></div>
        @endif
    </form>
</x-ui.card-section>
