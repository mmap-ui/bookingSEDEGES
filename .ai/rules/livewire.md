---
paths:
  - app/Livewire/VehicleManagement.php
  - 'app/Livewire/**'
  - app/Livewire/RoleManagement.php
  - app/Livewire/UserManagement.php
---

# Livewire

## Middlewares Spatie en bootstrap/app.php
El alias 'permission' del middleware de Spatie se registra en bootstrap/app.php con $middleware->alias(). Sin esto, las rutas fallan con 'Target class [permission] does not exist'.

## Vistas Livewire de página: raíz única
Cada vista de componente Livewire debe tener UNA sola raíz. El layout <x-app-layout> va en vistas separadas bajo resources/views/pages/ (que renderizan <livewire:.../>). Evita duplicar @livewireScripts dentro de la vista del componente (causa 'Multiple root elements').

## Forzar guard web al crear roles (sanctum en runtime)
Jetstream muta `auth.defaults.guard` a `sanctum` en runtime HTTP (via Auth::shouldUse), mientras que los roles/permisos sembrados usan guard `web`. Al crear roles con Spatie hay que forzar el guard del permiso almacenado: `Permission::query()->value('guard_name')`, no `config('auth.defaults.guard')`, o `syncPermissions` lanza GuardDoesNotMatch.

## password_confirmation debe usar snake_case para la regla confirmed
La regla de validación `confirmed` exige que el campo se llame `password_confirmation` (snake_case), no `passwordConfirmation`. El prop de Livewire y el atributo x-input a los que apunta `wire:model` deben usar exactamente `password_confirmation`, o el `save()` falla con "The password field confirmation does not match."

## Dispatch de Livewire mapea params por nombre
Livewire.dispatch('evento', { a: 1 }) mapea los parámetros por NOMBRE a los argumentos del método PHP. Un listener con firma open(?array $payload = null) recibe $payload=null si el JS envía { start, end }: las claves no coinciden con el nombre del parámetro y los datos se pierden silenciosamente (el modal abre vacío). Usar firmas con nombres idénticos a las claves del dispatch, p.ej. open(?string $start = null, ?string $end = null).
