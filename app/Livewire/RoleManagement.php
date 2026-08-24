<?php

namespace App\Livewire;

use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleManagement extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    /** @var array<int|string> */
    public array $permissions = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->editingId)],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function create(): void
    {
        $this->authorize('gestionar roles');

        $this->editingId = null;
        $this->resetValidation();
        $this->reset(['name', 'permissions']);
        $this->showModal = true;
    }

    public function edit(Role $role): void
    {
        $this->authorize('gestionar roles');

        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->permissions = $role->permissions->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorize('gestionar roles');

        $data = $this->validate();

        $guard = Permission::query()->value('guard_name') ?? config('auth.defaults.guard', 'web');

        $role = $this->editingId === null
            ? Role::query()->create(['name' => $data['name'], 'guard_name' => $guard])
            : Role::query()->findOrFail($this->editingId);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $role->syncPermissions(
            Permission::query()
                ->where('guard_name', $guard)
                ->whereIn('id', $this->permissions)
                ->get()
        );

        $this->reset(['editingId', 'name', 'permissions']);
        $this->showModal = false;
        session()->flash('status', 'Rol guardado correctamente.');
    }

    public function delete(Role $role): void
    {
        $this->authorize('gestionar roles');

        if ($role->name === 'administrador' || $role->users()->exists()) {
            session()->flash('error', 'No se puede eliminar un rol con usuarios asignados.');

            return;
        }

        $role->delete();
        session()->flash('status', 'Rol eliminado.');
    }

    public function render()
    {
        return view('livewire.role-management', [
            'roles' => Role::query()
                ->with('permissions')
                ->orderBy('name')
                ->get(),
            'allPermissions' => Permission::query()
                ->orderBy('name')
                ->get(),
        ]);
    }
}
