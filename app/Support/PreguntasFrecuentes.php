<?php

namespace App\Support;

/**
 * Preguntas frecuentes de la pantalla de Ayuda.
 *
 * El contenido vive aquí y no en la base porque cambia poco y conviene revisarlo como
 * cualquier otro texto del producto. Si en algún momento la administración necesita
 * editarlo sin desplegar, el paso natural es moverlo a `terminos_catalogo` con su
 * pantalla, igual que los catálogos profesionales.
 *
 * Cada pregunta declara a quién le sirve: quien entra solo ve las suyas y las generales,
 * porque a un postulante no le aporta leer sobre cupos de desbloqueo.
 */
final class PreguntasFrecuentes
{
    private const TODOS = 'todos';

    /**
     * @return list<array{pregunta: string, respuesta: string, audiencia: string}>
     */
    public static function todas(): array
    {
        return [
            ...self::generales(),
            ...self::dePostulantes(),
            ...self::deEmpresas(),
        ];
    }

    /**
     * Las que le sirven a ese rol: las suyas más las generales, en ese orden.
     *
     * @return list<array{pregunta: string, respuesta: string, audiencia: string}>
     */
    public static function para(?string $rol): array
    {
        $propias = match ($rol) {
            'postulante' => self::dePostulantes(),
            'empresa' => self::deEmpresas(),
            default => [],
        };

        return [...$propias, ...self::generales()];
    }

    /** @return list<array{pregunta: string, respuesta: string, audiencia: string}> */
    private static function generales(): array
    {
        return [
            [
                'audiencia' => self::TODOS,
                'pregunta' => '¿Qué es AD+50?',
                'respuesta' => 'Una plataforma chilena que conecta a profesionales mayores de 50 años con empresas que buscan su experiencia. No publicamos avisos abiertos a todo el mundo: las empresas definen los criterios de lo que buscan y el sistema les entrega solo a quienes los cumplen.',
            ],
            [
                'audiencia' => self::TODOS,
                'pregunta' => '¿Qué pasa con mis datos personales?',
                'respuesta' => 'Tratamos tus datos conforme a la Ley 21.719 de protección de datos personales, y por eso pedimos tu consentimiento al registrarte. Tus datos de contacto no son públicos: solo los ve una empresa con suscripción vigente que haya desbloqueado tu perfil, o a cuya oferta hayas postulado.',
            ],
            [
                'audiencia' => self::TODOS,
                'pregunta' => '¿Cómo cambio mi contraseña o mi correo?',
                'respuesta' => 'En el menú de tu nombre, arriba a la derecha, entra a «Mi cuenta». Ahí puedes cambiar tu correo, tu contraseña y activar la verificación en dos pasos.',
            ],
            [
                'audiencia' => self::TODOS,
                'pregunta' => 'Encontré un error o algo no funciona, ¿qué hago?',
                'respuesta' => 'Escríbenos con el formulario de abajo eligiendo «Soporte técnico». Cuéntanos qué intentabas hacer y qué viste: mientras más concreto, más rápido lo resolvemos.',
            ],
        ];
    }

    /** @return list<array{pregunta: string, respuesta: string, audiencia: string}> */
    private static function dePostulantes(): array
    {
        return [
            [
                'audiencia' => 'postulante',
                'pregunta' => '¿Cómo me encuentran las empresas?',
                'respuesta' => 'Cuando una empresa crea una búsqueda con sus criterios (cargo, carrera, industria, región, años de experiencia), el sistema la compara automáticamente con tu perfil. Apareces si cumples todos los criterios de esa búsqueda. No hay que postular a nada para ser encontrado.',
            ],
            [
                'audiencia' => 'postulante',
                'pregunta' => 'No aparezco en ninguna búsqueda, ¿por qué?',
                'respuesta' => 'Las causas más habituales son tres: tu perfil está pausado (revisa el interruptor en Oportunidades), tu ficha está incompleta en los campos por los que se busca —carrera, industria, región, años de experiencia—, o simplemente aún no hay búsquedas activas que calcen con tu trayectoria. Completar tu perfil es lo que más ayuda.',
            ],
            [
                'audiencia' => 'postulante',
                'pregunta' => '¿Qué ve una empresa de mi perfil?',
                'respuesta' => 'De entrada ve tu trayectoria profesional sin tus datos de identidad. Para acceder a tu nombre completo, tu teléfono, tu correo y tu CV, la empresa tiene que desbloquear tu perfil consumiendo un cupo de su plan. Si postulas a una oferta, esos datos quedan visibles para esa empresa.',
            ],
            [
                'audiencia' => 'postulante',
                'pregunta' => '¿Puedo dejar de aparecer temporalmente?',
                'respuesta' => 'Sí. En Oportunidades tienes el interruptor «Visible para reclutadores». Al apagarlo tu perfil deja de aparecer en las búsquedas de inmediato, y tus datos siguen guardados para cuando quieras volver a activarlo.',
            ],
            [
                'audiencia' => 'postulante',
                'pregunta' => '¿Es gratis para mí?',
                'respuesta' => 'Crear tu perfil y aparecer en las búsquedas no tiene costo. Quienes pagan una suscripción son las empresas que buscan candidatos.',
            ],
        ];
    }

    /** @return list<array{pregunta: string, respuesta: string, audiencia: string}> */
    private static function deEmpresas(): array
    {
        return [
            [
                'audiencia' => 'empresa',
                'pregunta' => '¿Cómo funciona una búsqueda?',
                'respuesta' => 'En Prospección de Candidatos defines los criterios del perfil que necesitas y el sistema te entrega a quienes los cumplen todos, sin que tengas que revisar postulaciones que no calzan. La búsqueda queda guardada y se actualiza sola: si mañana alguien completa su ficha y pasa a cumplir tus criterios, aparecerá ahí.',
            ],
            [
                'audiencia' => 'empresa',
                'pregunta' => '¿Qué es un desbloqueo?',
                'respuesta' => 'Los resultados muestran la trayectoria del candidato sin sus datos de identidad. Desbloquear un perfil consume un cupo de tu plan y te da acceso a su nombre completo, teléfono, correo y CV. Cada candidato se desbloquea una sola vez: si vuelves a abrirlo, no se te cobra de nuevo.',
            ],
            [
                'audiencia' => 'empresa',
                'pregunta' => '¿Por qué mi cuenta necesita activación?',
                'respuesta' => 'Revisamos manualmente cada empresa antes de darle acceso a los perfiles, porque detrás de cada ficha hay datos personales de una persona real. Envía tus antecedentes desde la pantalla de activación y te habilitamos la cuenta.',
            ],
            [
                'audiencia' => 'empresa',
                'pregunta' => '¿Puedo sumar a más gente de mi equipo?',
                'respuesta' => 'Sí. Desde «Administración de usuarios», en el menú de tu nombre, puedes invitar a colegas. Comparten los candidatos guardados de la cuenta, y cada uno mantiene sus propias notas.',
            ],
            [
                'audiencia' => 'empresa',
                'pregunta' => '¿Qué pasa cuando vence mi plan?',
                'respuesta' => 'Tus búsquedas, tus candidatos guardados y tus publicaciones se conservan, pero dejas de poder desbloquear perfiles nuevos hasta renovar. Puedes revisar el estado de tu plan en «Mi suscripción».',
            ],
        ];
    }
}
