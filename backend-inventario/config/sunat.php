<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API de Consulta SUNAT
    |--------------------------------------------------------------------------
    |
    | Configuración para consultar RUC vía API gratuita.
    | Opciones recomendadas: apis.net.pe, apisperu.com
    |
    */

    'api_url' => env('SUNAT_API_URL', 'https://api.apis.net.pe/v2'),

    'token' => env('SUNAT_API_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Tiempo de Cache
    |--------------------------------------------------------------------------
    |
    | Tiempo en segundos para cachear las consultas de RUC.
    | Por defecto: 24 horas (86400 segundos)
    |
    */

    'cache_ttl' => env('SUNAT_CACHE_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Timeout de Conexión
    |--------------------------------------------------------------------------
    |
    | Tiempo máximo de espera para la respuesta de la API en segundos.
    |
    */

    'timeout' => env('SUNAT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Permitir Registro Manual
    |--------------------------------------------------------------------------
    |
    | Si la API no responde, permite registrar proveedores manualmente
    | con una advertencia de que los datos no fueron verificados.
    |
    */

    'permite_registro_manual' => env('SUNAT_PERMITE_MANUAL', true),
];
