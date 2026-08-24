import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';

export function initCalendarApp(options) {
    return {
        calendar: null,
        selectedEvent: null,
        confirmingCancel: false,
        confirmingApprove: false,
        authUserId: options.authUserId ?? null,
        isApprover: options.isApprover ?? false,
        canCreate: options.canCreate ?? false,

        init() {
            this.calendar = new Calendar(this.$refs.calendar, {
                plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
                locale: esLocale,
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay',
                },
                height: 'auto',
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                allDaySlot: false,
                selectable: this.canCreate,
                selectMirror: false,
                dayMaxEventRows: true,
                eventSources: [{ url: options.endpoint, method: 'GET' }],
                eventClick: (info) => {
                    this.selectedEvent = info.event.extendedProps;
                },
                select: (info) => {
                    this.openReservationForm(info.startStr, info.endStr);
                    this.calendar.unselect();
                },
            });

            this.calendar.render();

            const subscribe = () => Livewire.on('calendar-filters-updated', () => this.refresh());

            if (window.Livewire) {
                subscribe();
            } else {
                document.addEventListener('livewire:init', subscribe);
            }
        },

        refresh() {
            const vehicleEl = document.getElementById('calendar-vehicle-filter');
            const statusEl = document.getElementById('calendar-status-filter');
            const url = new URL(options.endpoint, window.location.origin);

            if (vehicleEl && vehicleEl.value) {
                url.searchParams.set('vehicle', vehicleEl.value);
            }

            if (statusEl && statusEl.value && statusEl.value !== 'todas') {
                url.searchParams.set('status', statusEl.value);
            }

            this.calendar.removeAllEventSources();
            this.calendar.addEventSource({ url: url.toString(), method: 'GET' });
            this.calendar.refetchEvents();
        },

        openReservationForm(start, end) {
            Livewire.dispatch('open-reservation-form', { start, end });
        },

        closeDetails() {
            this.selectedEvent = null;
            this.confirmingCancel = false;
            this.confirmingApprove = false;
        },

        requestCancel() {
            if (!this.selectedEvent) {
                return;
            }

            Livewire.dispatch('request-reservation-cancel', { reservationId: this.selectedEvent.id });
            this.confirmingCancel = false;
            this.selectedEvent = null;
        },

        requestApprove(reservationId = null) {
            const targetId = reservationId ?? this.selectedEvent?.id;

            if (!targetId) {
                return;
            }

            Livewire.dispatch('request-reservation-approve', { reservationId: targetId });
            this.confirmingApprove = false;
            this.selectedEvent = null;
        },
    };
}