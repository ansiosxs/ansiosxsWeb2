<?php

return [
    'plazo_prestamo_dias' => (int) env('BIBLIOTECA_PLAZO_PRESTAMO_DIAS', 14),
    'max_prestamos_activos' => (int) env('BIBLIOTECA_MAX_PRESTAMOS_ACTIVOS', 2),
    'dias_recordatorio' => (int) env('BIBLIOTECA_DIAS_RECORDATORIO', 2),
];
