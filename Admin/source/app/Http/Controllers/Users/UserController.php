<?php

namespace Modules\Admin\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Modules\Admin\Support\ActivityLogger;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display the user management page.
     */
    public function index(): View
    {
        $users = User::with('roles')
            ->orderBy('id')
            ->get()
            ->map(fn (User $user): array => $this->transform($user));

        $roleOptions = Role::orderBy('name')->pluck('display_name', 'id');

        $defaultRoleId = Role::where('name', 'user')->value('id');

        return view('admin::users.index', [
            'users' => $users,
            'roleOptions' => $roleOptions,
            'defaultRoleId' => $defaultRoleId,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateUser($request, forCreate: true);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'],
        ]);

        // An admin-created account is treated as already verified — there's no
        // self-registration flow to confirm it through. Not mass-assignable
        // (not in User's #[Fillable(...)] list), so set directly.
        $user->forceFill(['email_verified_at' => now()])->save();

        $user->syncRoles([$data['role_id']]);

        ActivityLogger::record('admin', 'user_created', "Created user account for \"{$user->email}\".");

        return response()->json($this->transform($user->load('roles')), 201);
    }

    /**
     * Update the given user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $this->validateUser($request, forCreate: false, user: $user);
        $previousRole = $user->roles->first();

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $previousRole?->display_name,
            'is_active' => $user->is_active,
        ];

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
        ]);

        $passwordChanged = ! empty($data['password']);
        if ($passwordChanged) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $user->syncRoles([$data['role_id']]);

        $newRole = Role::find($data['role_id']);
        $after = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $newRole?->display_name,
            'is_active' => $user->is_active,
            // Never log the password itself — only whether it changed.
            'password_changed' => $passwordChanged,
        ];

        $message = $newRole && $previousRole?->id !== $newRole->id
            ? "Changed role of \"{$user->email}\" from {$previousRole?->display_name} to {$newRole->display_name}."
            : "Updated profile details for \"{$user->email}\".";

        ActivityLogger::record(
            'admin',
            'user_updated',
            $message,
            properties: ['before' => $before, 'after' => $after],
        );

        return response()->json($this->transform($user->load('roles')));
    }

    /**
     * Remove the given user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => "You can't delete your own account.",
            ], 422);
        }

        ActivityLogger::record('admin', 'user_deleted', "Deleted user account \"{$user->email}\".");

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    /**
     * Force-disable two-factor authentication for the given user.
     */
    public function disableTwoFactor(User $user): JsonResponse
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        ActivityLogger::record('admin', 'user_two_factor_force_disabled', "Force-disabled two-factor authentication for \"{$user->email}\".");

        return response()->json($this->transform($user->load('roles')));
    }

    /**
     * @return array{name: string, email: string, password: ?string, is_active: bool, role_id: int}
     */
    private function validateUser(Request $request, bool $forCreate, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [$forCreate ? 'required' : 'nullable', 'string', Password::default(), 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        $data['is_active'] = (bool) $data['is_active'];

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(User $user): array
    {
        $role = $user->roles->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $role?->id,
            'role_name' => $role?->display_name ?? 'No role',
            'role_variant' => $this->roleVariant($role?->name),
            'is_active' => $user->is_active,
            'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            'is_admin' => $user->hasRole('admin'),
            'created_at' => $user->created_at->timestamp,
            'registered_label' => $user->created_at->format('M j, Y, g:i A'),
        ];
    }

    private function roleVariant(?string $roleName): string
    {
        return match ($roleName) {
            null => 'secondary',
            'admin' => 'primary',
            'user' => 'secondary',
            default => ['info', 'warning', 'success', 'danger'][crc32($roleName) % 4],
        };
    }
}
