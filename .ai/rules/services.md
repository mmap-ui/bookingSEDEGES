---
paths:
  - app/Services/ReservationService.php
  - 'app/Services/**'
---

# Services

## Validación de solapamiento de reservas
Solo las reservas APROBADAS bloquean rangos; las pendientes no. Al aprobar se re-valida el solapamiento (lockForUpdate sobre el vehículo en transacción) para evitar double-booking. Regla reutilizable en App\Rules\NoApprovedReservationOverlap.

## Carbon 3: diffInSeconds es firmado
Carbon::diffInSeconds() es con signo: end->diffInSeconds(start) devuelve negativo. Calcular siempre desde el inicio recortado hacia el fin recortado (start->diffInSeconds(end)). Este patrón erróneo hizo que "Horas reservadas" mostrara 0 en reportes.
