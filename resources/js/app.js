import { initCalendarApp } from './calendar';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('calendarApp', initCalendarApp);

    window.Alpine.data('realtimeClock', (serverTime) => ({
        time: '',
        date: '',
        offsetMs: 0,

        init() {
            this.offsetMs = Date.now() - new Date(serverTime).getTime();
            this.update();
            setInterval(() => this.update(), 1000);
        },

        update() {
            const now = new Date(Date.now() - this.offsetMs);
            this.time = now.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
            this.date = now.toLocaleDateString('es-ES', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });
        },
    }));
});