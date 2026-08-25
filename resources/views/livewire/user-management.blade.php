<div>
    @if (session('status'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-md">
            {{ session('status') }}
        </div>
    @endif

    @error('users')
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-md">
            {{ $message }}
        </div>
    @enderror

    @can('gestionar usuarios')
        <div class="mb-4">
            <x-button wire:click="create">
                {{ __('Nuevo Usuario') }}
            </x-button>
        </div>

        {{-- Buscador por nombre o email --}}
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="relative flex-1">
                <x-input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="{{ __('Buscar usuario por nombre o correo...') }}" class="w-full pl-10" />
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>
    @endcan

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Correo
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $user->name }}
                            @if ($user->is(auth()->user()))
                                <span class="text-gray-400">({{ __('usted') }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $user->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                {{ $user->getRoleNames()->first() ?? 'Sin rol' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($user->is_active)
                                <span
                                    class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                    Activo
                                </span>
                            @else
                                <span
                                    class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                    Inactivo
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @can('gestionar usuarios')
                                <button wire:click="edit({{ $user->id }})"
                                    class="text-indigo-600 hover:text-indigo-900">Editar</button>
                                @unless ($user->is(auth()->user()))
                                    <button wire:click="delete({{ $user->id }})"
                                        wire:confirm="{{ __('¿Eliminar este usuario?') }}"
                                        class="ml-3 text-red-600 hover:text-red-900">Eliminar</button>
                                @endunless
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                            {{ __('No hay usuarios registrados.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

    {{-- Paginacion --}}
    <div class="mt-4 pt-3 border-t">
        {{ $users->links() }}
    </div>

    {{-- Formulario de creacion o edicion de nuevo usuario  --}}
    <x-dialog-modal wire:model="showModal" maxWidth="lg">
        <x-slot name="title">
            {{ $editingId === null ? __('Nuevo Usuario') : __('Editar Usuario') }}
        </x-slot>

        <x-slot name="content">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <x-label value="{{ __('Nombre completo') }}" />
                    <x-input wire:model="name" type="text" class="mt-1 block w-full" autocomplete="off" />
                    <x-input-error for="name" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Correo electrónico') }}" />
                    <x-input wire:model="email" type="email" class="mt-1 block w-full" autocomplete="off" />
                    <x-input-error for="email" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Rol') }}" />
                    <select wire:model="role"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="role" class="mt-2" />
                </div>

                {{-- Campo de Estado (Activo / Inactivo) --}}
                <div class="flex items-center pt-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="is_active"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-5 w-5">
                        <span class="ml-2 text-sm text-gray-700 font-medium">{{ __('Usuario Activo') }}</span>
                    </label>
                    <x-input-error for="is_active" class="mt-2" />
                </div>

                <div>
                    <x-label
                        value="{{ $editingId === null ? __('Contraseña') : __('Nueva contraseña (dejar vacío para no cambiar)') }}" />
                    <x-input wire:model="password" type="password" class="mt-1 block w-full"
                        autocomplete="new-password" />
                    <x-input-error for="password" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Confirmar contraseña') }}" />
                    <x-input wire:model="password_confirmation" type="password" class="mt-1 block w-full"
                        autocomplete="new-password" />
                    <x-input-error for="password_confirmation" class="mt-2" />
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="close" class="mr-2">{{ __('Cancelar') }}</x-secondary-button>
            <x-button wire:click="save" wire:loading.attr="disabled">{{ __('Guardar') }}</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
