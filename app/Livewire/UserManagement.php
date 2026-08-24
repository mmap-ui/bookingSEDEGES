<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class UserManagement extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public ?string $password_confirmation = null;

    public ?string $role = 'chofer';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => [$this->editingId === null ? 'required' : 'nullable', 'string', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(['administrador', 'responsable', 'chofer', 'consultas'])],
        ];
    }

    public function create(): void
    {
        $this->authorize('gestionar usuarios');

        $this->editingId = null;
        $this->resetValidation();
        $this->reset(['name', 'email', 'password', 'password_confirmation']);
        $this->role = 'chofer';
        $this->showModal = true;
    }

    public function edit(User $user): void
    {
        $this->authorize('gestionar usuarios');

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
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
                'email_verified_at' => now(),
            ]);
            $user->syncRoles([$data['role']]);
            session()->flash('status', 'Usuario creado correctamente.');
        } else {
            $user = User::query()->findOrFail($this->editingId);
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
            if (filled($data['password'])) {
                $user->update(['password' => $data['password']]);
            }
            $user->syncRoles([$data['role']]);
            session()->flash('status', 'Usuario actualizado correctamente.');
        }

        $this->reset(['editingId', 'name', 'email', 'password', 'password_confirmation']);
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
                ->orderBy('name')
                ->get(),
        ]);
    }
}
