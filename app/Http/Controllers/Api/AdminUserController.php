<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    private const ROLES = ['customer', 'sales_staff', 'order_manager', 'admin', 'super_admin'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(self::ROLES)],
            'active' => ['nullable', 'boolean'],
            'verified' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->withCount(['orders', 'inquiries', 'addresses', 'wishlistItems'])
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->when($validated['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when(array_key_exists('active', $validated), fn ($query) => $query->where('is_active', $validated['active']))
            ->when(array_key_exists('verified', $validated), fn ($query) => $validated['verified']
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at'))
            ->latest()
            ->paginate($validated['per_page'] ?? 20);

        return AdminUserResource::collection($users);
    }

    public function store(Request $request): AdminUserResource
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'role' => ['required', Rule::in(self::ROLES)],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user = new User;
        $user->forceFill([
            ...$data,
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
        ])->save();
        $user->sendEmailVerificationNotification();

        return new AdminUserResource($this->withActivity($user));
    }

    public function show(User $user): AdminUserResource
    {
        return new AdminUserResource($this->withActivity($user, true));
    }

    public function update(Request $request, User $user): AdminUserResource
    {
        $actor = $request->user();

        if ($request->has('email')) {
            $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        }

        if ($actor->role !== 'super_admin' && $user->role !== 'customer') {
            abort(403, 'Only super administrators can manage staff accounts.');
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('users')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(self::ROLES)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('role', $data) && $actor->role !== 'super_admin') {
            abort(403, 'Only super administrators can assign roles.');
        }

        if ($actor->is($user) && (array_key_exists('role', $data) || array_key_exists('is_active', $data))) {
            throw ValidationException::withMessages([
                'account' => ['You cannot change your own role or active state.'],
            ]);
        }

        $removesActiveSuperAdmin = $user->role === 'super_admin'
            && $user->is_active
            && (($data['role'] ?? $user->role) !== 'super_admin' || ($data['is_active'] ?? true) === false);

        if ($removesActiveSuperAdmin && User::where('role', 'super_admin')->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'account' => ['The last active super administrator cannot be demoted or deactivated.'],
            ]);
        }

        $emailChanged = isset($data['email']) && strtolower($data['email']) !== $user->email;
        $deactivated = array_key_exists('is_active', $data) && $data['is_active'] === false;

        DB::transaction(function () use ($user, &$data, $emailChanged, $deactivated) {
            if (isset($data['email'])) {
                $data['email'] = strtolower($data['email']);
            }

            if ($emailChanged) {
                $data['email_verified_at'] = null;
            }

            $user->forceFill($data)->save();

            if ($deactivated || $emailChanged) {
                $user->tokens()->delete();
            }
        });

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return new AdminUserResource($this->withActivity($user->refresh()));
    }

    private function withActivity(User $user, bool $includeRecent = false): User
    {
        $user->loadCount(['orders', 'inquiries', 'addresses', 'wishlistItems']);

        if ($includeRecent) {
            $user->load([
                'orders' => fn ($query) => $query->with(['items', 'payments'])->latest('ordered_at')->limit(10),
                'inquiries' => fn ($query) => $query->with('service')->latest('submitted_at')->limit(10),
            ]);
        }

        return $user;
    }
}
