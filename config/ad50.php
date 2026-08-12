<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Interruptores de funcionalidad
    |--------------------------------------------------------------------------
    |
    | Las dos nacieron apagadas mientras se revisaban y ya están publicadas, así que
    | el valor por omisión pasa a ser true: se ven en todos los entornos sin depender
    | de que nadie defina nada.
    |
    | El interruptor se conserva como apagado de emergencia: si algo saliera mal,
    | basta con definir la variable en false en el entorno —sin desplegar código— y
    | la funcionalidad desaparece de la vista. El código y sus tablas siguen intactos.
    |
    */

    'funcionalidades' => [

        // Aviso en Oportunidades y tarjeta en la ficha con lo que falta del perfil.
        // Ojo: esto NO controla el cálculo de `completitud` ni los indicadores de
        // porcentaje (la píldora de Oportunidades y la barra de la ficha), que
        // muestran el valor real aunque el detalle de qué falta esté oculto.
        'recomendaciones_perfil' => (bool) env('AD50_RECOMENDACIONES_PERFIL', true),

        // Carpetas para agrupar los favoritos de la empresa.
        'carpetas_favoritos' => (bool) env('AD50_CARPETAS_FAVORITOS', true),

    ],

];
