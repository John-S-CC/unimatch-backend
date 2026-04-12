<?php
// Compatibilidad temporal: este archivo delega al validador activo.
if (!class_exists("ValidadorHorarios", false)) {
    require_once __DIR__ . '/validadores/ValidadorHorarios.php';
}
