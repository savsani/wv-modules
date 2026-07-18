<x-ui.card-section :title="__('Update Password')" :description="__('Ensure your account is using a long, random password to stay secure.')">
    <form method="POST" action="{{ route('user-password.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <x-form.field label="Current Password" for="current_password" required :error="$errors->updatePassword->first('current_password')">
            <x-form.password-input id="current_password" name="current_password" required autocomplete="current-password" :error="$errors->updatePassword->has('current_password')" />
        </x-form.field>

        <x-form.field label="New Password" for="password" required :error="$errors->updatePassword->first('password')">
            <x-form.password-input id="password" name="password" required autocomplete="new-password" :error="$errors->updatePassword->has('password')" />
        </x-form.field>

        <x-form.field label="Confirm New Password" for="password_confirmation" required :error="$errors->updatePassword->first('password_confirmation')">
            <x-form.password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" :error="$errors->updatePassword->has('password_confirmation')" />
        </x-form.field>

        <x-ui.button type="submit" variant="primary" style="solid">Save</x-ui.button>

        @if(session('status') === \Laravel\Fortify\Fortify::PASSWORD_UPDATED)
            <div x-init="$store.toast.show({ message: 'Password updated.', type: 'success' })"></div>
        @endif
    </form>
</x-ui.card-section>
