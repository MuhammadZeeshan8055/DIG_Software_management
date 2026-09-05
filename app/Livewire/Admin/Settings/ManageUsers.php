<?php

namespace App\Livewire\Admin\Settings;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ManageUsers extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'staff';

    /** When true, staff gets manage on every module feature. */
    public bool $allowAllAccess = false;

    /** @var array<string, bool> module_key => enabled */
    public array $moduleEnabled = [];

    /** @var array<string, string> module_key => view|manage */
    public array $moduleLevel = [];

    public ?string $successMessage = null;

    public bool $showModal = false;

    public function mount(): void
    {
        $this->resetModuleAccess();
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()->canManageUsers(), 403);

        $this->resetFormFields();
        $this->showModal = true;
        $this->successMessage = null;
        $this->js('document.body.classList.add("mu-modal-open")');
    }

    public function updatedAllowAllAccess(bool $value): void
    {
        foreach (array_keys($this->moduleEnabled) as $moduleKey) {
            $this->moduleEnabled[$moduleKey] = $value;
            $this->moduleLevel[$moduleKey] = 'manage';
        }
    }

    public function editUser(int $id): void
    {
        abort_unless(auth()->user()->canManageUsers(), 403);

        $user = User::with('permissions')->findOrFail($id);

        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->role;
        $this->allowAllAccess = false;
        $this->resetModuleAccess();

        if ($user->role === 'staff') {
            $this->loadModuleAccessFromPermissions($user);
        }

        $this->resetValidation();
        $this->successMessage = null;
        $this->showModal = true;
        $this->js('document.body.classList.add("mu-modal-open")');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetFormFields();
        $this->js('document.body.classList.remove("mu-modal-open")');
    }

    public function saveUser(): void
    {
        abort_unless(auth()->user()->canManageUsers(), 403);

        $actor = auth()->user();

        $allowedRoles = $actor->isSuperAdmin()
            ? ['super_admin', 'admin', 'staff']
            : ['admin', 'staff'];

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:120',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'role' => ['required', Rule::in($allowedRoles)],
            'allowAllAccess' => ['boolean'],
            'moduleEnabled' => ['array'],
            'moduleLevel' => ['array'],
            'moduleLevel.*' => ['in:view,manage'],
        ];

        if ($this->editingId) {
            $rules['password'] = ['nullable', 'string', 'min:8'];
        } else {
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        $this->validate($rules);

        $wasEditing = (bool) $this->editingId;

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);

            if ($user->isSuperAdmin() && ! $actor->isSuperAdmin()) {
                abort(403);
            }

            $user->name = trim($this->name);
            $user->email = trim($this->email);
            $user->role = $this->role;

            if ($this->password !== '') {
                $user->password = $this->password;
            }

            $user->save();
        } else {
            $user = User::create([
                'name' => trim($this->name),
                'email' => trim($this->email),
                'password' => $this->password,
                'role' => $this->role,
                'email_verified_at' => now(),
            ]);
        }

        $this->syncPermissions($user);
        $this->closeModal();
        $this->successMessage = $wasEditing ? 'User updated.' : 'User created.';
    }

    public function deleteUser(int $id): void
    {
        abort_unless(auth()->user()->canManageUsers(), 403);

        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->addError('email', 'You cannot delete your own account.');

            return;
        }

        if ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $user->delete();
        $this->successMessage = 'User deleted.';

        if ($this->editingId === $id) {
            $this->closeModal();
        }
    }

    protected function syncPermissions(User $user): void
    {
        UserPermission::where('user_id', $user->id)->delete();

        if ($user->role !== 'staff') {
            return;
        }

        foreach ($this->assignableModules() as $module) {
            $moduleKey = $module['key'];

            if ($this->allowAllAccess) {
                $access = 'manage';
            } elseif (! empty($this->moduleEnabled[$moduleKey])) {
                $access = $this->moduleLevel[$moduleKey] ?? 'manage';
            } else {
                continue;
            }

            foreach ($module['children'] ?? [] as $child) {
                // Never grant admin-only features to staff
                if (! empty($child['admin_only'])) {
                    continue;
                }

                UserPermission::create([
                    'user_id' => $user->id,
                    'module_key' => $moduleKey,
                    'feature_key' => $child['key'],
                    'access' => $access,
                ]);
            }
        }
    }

    protected function loadModuleAccessFromPermissions(User $user): void
    {
        $byModule = [];

        foreach ($user->permissions as $permission) {
            $module = $permission->module_key;
            $access = $permission->access;

            if (! isset($byModule[$module]) || $access === 'manage') {
                $byModule[$module] = $access;
            }
        }

        $allOn = count($byModule) === count($this->assignableModules());

        foreach ($this->assignableModules() as $module) {
            $key = $module['key'];
            $childCount = collect($module['children'] ?? [])
                ->reject(fn (array $child) => ! empty($child['admin_only']))
                ->count();
            $savedCount = $user->permissions->where('module_key', $key)->count();

            if ($savedCount > 0) {
                $this->moduleEnabled[$key] = true;
                $this->moduleLevel[$key] = $byModule[$key] ?? 'view';

                if ($savedCount !== $childCount || ($byModule[$key] ?? '') !== 'manage') {
                    $allOn = false;
                }
            } else {
                $this->moduleEnabled[$key] = false;
                $this->moduleLevel[$key] = 'manage';
                $allOn = false;
            }
        }

        $this->allowAllAccess = $allOn;
    }

    protected function resetFormFields(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'staff';
        $this->allowAllAccess = false;
        $this->resetModuleAccess();
        $this->resetValidation();
    }

    protected function resetModuleAccess(): void
    {
        $this->moduleEnabled = [];
        $this->moduleLevel = [];

        foreach ($this->assignableModules() as $module) {
            $this->moduleEnabled[$module['key']] = false;
            $this->moduleLevel[$module['key']] = 'manage';
        }
    }

    protected function assignableModules(): array
    {
        return collect(config('admin.modules', []))
            ->reject(fn (array $module) => ($module['key'] ?? '') === 'settings')
            ->values()
            ->all();
    }

    public function render()
    {
        if (! auth()->user()->canManageUsers()) {
            return view('livewire.admin.settings.manage-users', [
                'users' => collect(),
                'assignableModules' => [],
                'roleOptions' => [],
                'denied' => true,
            ]);
        }

        return view('livewire.admin.settings.manage-users', [
            'users' => User::query()->orderBy('name')->get(),
            'assignableModules' => $this->assignableModules(),
            'roleOptions' => auth()->user()->isSuperAdmin()
                ? [
                    'super_admin' => 'Super Admin',
                    'admin' => 'Admin',
                    'staff' => 'Staff',
                ]
                : [
                    'admin' => 'Admin',
                    'staff' => 'Staff',
                ],
            'denied' => false,
        ]);
    }
}
