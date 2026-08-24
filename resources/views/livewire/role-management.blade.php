<div>
    @if (session('status'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-md">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-md">
            {{ session('error') }}
        </div>
    @endif

    @can('gestionar roles')
        <div class="mb-4">
            <x-button wire:click="create">
                {{ __('Nuevo Rol') }}
            </x-button>
        </div>
    @endcan

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permisos</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($roles as $role)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $role->name }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($role->permissions as $permission)
                                    <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                        {{ $permission->name }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400">Sin permisos</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @can('gestionar roles')
                                <button wire:click="edit({{ $role->id }})" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                                @unless ($role->name === 'administrador')
                                    <button
                                        wire:click="delete({{ $role->id }})"
                                        wire:confirm="{{ __('¿Eliminar este rol?') }}"
                                        class="ml-3 text-red-600 hover:text-red-900"
                                    >Eliminar</button>
                                @endunless
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center text-sm text-gray-500">
                            {{ __('No hay roles registrados.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-dialog-modal wire:model="showModal" maxWidth="2xl">
        <x-slot name="title">
            {{ $editingId === null ? __('Nuevo Rol') : __('Editar Rol') }}
        </x-slot>

        <x-slot name="content">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <x-label value="{{ __('Nombre del rol') }}" />
                    <x-input wire:model="name" type="text" class="mt-1 block w-full" autocomplete="off" />
                    <x-input-error for="name" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Permisos') }}" />
                    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($allPermissions as $permission)
                            <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                                <x-checkbox wire:model="permissions" :value="$permission->id" />
                                <span class="text-sm text-gray-700">{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error for="permissions" class="mt-2" />
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="close" class="mr-2">{{ __('Cancelar') }}</x-secondary-button>
            <x-button wire:click="save" wire:loading.attr="disabled">{{ __('Guardar') }}</x-button>
        </x-slot>
    </x-dialog-modal>
</div>