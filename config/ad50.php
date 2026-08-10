<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Funcionalidades ocultas
    |--------------------------------------------------------------------------
    |
    | Funcionalidades ya terminadas y probadas, pero que todavía no se muestran.
    | El código y sus tablas siguen en su sitio: solo se apaga lo que la persona ve.
    |
    | Van APAGADAS por omisión, a propósito: así producción queda tapada sin depender
    | de que alguien recuerde definir la variable. Para trabajarlas en local, ponlas
    | en true en tu .env; para publicarlas, defínelas en el entorno correspondiente.
    |
    */

    'funcionalidades' => [

        // Aviso en Oportunidades y tarjeta en la ficha con lo que falta del perfil.
        // Ojo: NO apaga el cálculo de `completitud` ni los indicadores de porcentaje
        // (la píldora de Oportunidades y la barra de la ficha), que siguen mostrando
        // el valor real aunque el detalle de qué falta esté oculto.
        'recomendaciones_perfil' => (bool) env('AD50_RECOMENDACIONES_PERFIL', false),

        // Carpetas para agrupar los favoritos de la empresa.
        'carpetas_favoritos' => (bool) env('AD50_CARPETAS_FAVORITOS', false),

    ],

];
