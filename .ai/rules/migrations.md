---
paths:
  - 'database/migrations/**'
---

# Migrations

## MySQL 5.7: evita 'year()' y '->after()'
El servidor es MySQL 5.7 con strict mode. No usar $table->year() (usa unsignedSmallInteger) ni ->after() (no soportado). Usar dateTime en vez de timestamp para columnas de reserva para evitar el error 1067.
