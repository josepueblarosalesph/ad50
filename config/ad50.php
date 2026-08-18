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

    /*
    |--------------------------------------------------------------------------
    | Superadministrador
    |--------------------------------------------------------------------------
    |
    | Cuenta que SuperadminSeeder crea o promueve. La credencial real vive donde la
    | de cualquier usuario: hasheada en `users.password`. Esto es solo la semilla que
    | se usa UNA vez, al crear la cuenta; si ya existe, el seeder respeta su clave.
    |
    | `password` no tiene valor por omisión a propósito: una cuenta con todos los
    | privilegios no puede nacer con una clave adivinable porque alguien olvidó
    | definir la variable. Sin ella, el seeder inventa una al azar y el acceso se
    | obtiene por "olvidé mi contraseña" (ver SuperadminSeeder).
    |
    */

    'superadmin' => [
        'email' => env('SUPERADMIN_EMAIL', 'jose.puebla.rosales@gmail.com'),
        'password' => env('SUPERADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Contacto
    |--------------------------------------------------------------------------
    |
    | Casilla que atiende el soporte técnico. Los mensajes del formulario de Ayuda
    | con ese motivo se le envían solo a ella; el resto de los motivos van a todas
    | las cuentas con rol admin o superadmin (ver Ayuda::destinatarios).
    |
    | Va aquí y no incrustada en el código para poder cambiarla por entorno sin
    | desplegar: si mañana el soporte se atiende desde otra casilla, basta la
    | variable. El valor por omisión es la casilla en uso hoy.
    |
    */

    'contacto' => [
        'soporte' => env('AD50_EMAIL_SOPORTE', 'contacto.ad50.portal@gmail.com'),
    ],

];
