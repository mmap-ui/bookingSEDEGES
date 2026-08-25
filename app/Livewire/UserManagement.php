<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Spatie\Permission\Models\Role;

use Livewire\WithPagination;

class UserManagement extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public ?string $password_confirmation = null;

    public ?string $role = 'chofer';

    use WithPagination;
    public string $search = '';
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId === null ? 'required' : 'nullable', 'string', 'confirmed', Password::min(8)],
            'is_active' => 'boolean',
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // Crear nuevos usuarios
    public function create(): void
    {
        $this->authorize('gestionar usuarios');
        $this->editingId = null;
        $this->resetValidation();
        $this->reset(['name', 'email', 'password', 'password_confirmation']);
        $this->is_active = true;
        $this->role = Role::query()->value('name') ?? '';
        $this->showModal = true;
    }

    public function edit(User $user): void
    {
        $this->authorize('gestionar usuarios');

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_active = (bool) $user->is_active;
        $this->password = '';
        $this->password_confirmation = null;
        $this->role = $user->roles->pluck('name')->first() ?? 'chofer';
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
        $this->authorize('gestionar usuarios');

        $data = $this->validate();

        if ($data['role'] === 'administrador' && ! auth()->user()->hasRole('administrador')) {
            $this->addError('role', 'Solo un administrador puede asignar el rol de administrador.');

            return;
        }

        if ($this->editingId === null) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => $data['is_active'] ?? true,
                'email_verified_at' => now(),
            ]);
            $user->syncRoles([$data['role']]);
            session()->flash('status', 'Usuario creado correctamente.');
        } else {
            $user = User::query()->findOrFail($this->editingId);
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'] ?? true,
            ]);
            if (filled($data['password'])) {
                $user->update(['password' => $data['password']]);
            }
            $user->syncRoles([$data['role']]);
            session()->flash('status', 'Usuario actualizado correctamente.');
        }

        $this->reset(['editingId', 'name', 'email', 'password', 'password_confirmation','is_active']);
        $this->role = 'chofer';
        $this->showModal = false;
    }

    public function delete(User $user): void
    {
        $this->authorize('gestionar usuarios');

        if ($user->is(auth()->user())) {
            $this->addError('users', 'No puede eliminar su propia cuenta.');
            return;
        }

        $user->delete();
        session()->flash('status', 'Usuario eliminado.');
    }

    public function render()
    {
        return view('livewire.user-management', [
            'users' => User::query()
                ->with('roles')
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy('name')
                ->paginate(15),
            'roles' => Role::all(),
        ]);
    }
}
