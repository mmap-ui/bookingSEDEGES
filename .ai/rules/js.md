---
paths:
  - resources/js/calendar.js
  - 'resources/js/*.js'
---

# Js

## Acciones de calendario: Livewire.dispatch + #[On]
Las acciones de la UI del calendario (Alpine, dentro de wire:ignore) se comunican con la backend vía Livewire.dispatch('request-reservation-cancel', {reservationId}) y el componente App\Livewire\Calendar las escucha con #[On(...)]. Los eventos expuestos por CalendarEventController llevan en extendedProps: id, requesterId para decidir la visibilidad del botón.

## Suscribirse a eventos Livewire sin depender de livewire:init tardío
Livewire 3 dispara `document` `livewire:init` ANTES de iniciar Alpine (module_default.start). Por eso escucharlo dentro del `init()` del x-data nunca se ejecuta. Para reaccionar a eventos globales de Livewire desde un componente Alpine: suscribirse directo con `Livewire.on(...)` si `window.Livewire` ya existe, con fallback a `document.addEventListener('livewire:init', ...)`. Vite carga app.js antes que livewire.js, así que en `init()` de Alpine `window.Livewire` ya está disponible.
