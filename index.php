<?php
/**
 * Entrada principal para Hostinger y servidores compartidos (Apache/LiteSpeed).
 * Garantiza que si el servidor busca index.php en public_html, cargue la aplicación sin errores.
 */
require_once __DIR__ . '/public/index.php';
