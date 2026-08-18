<?php

namespace App\Livewire;

use App\Mail\MensajeContactoRecibido;
use App\Models\MensajeContacto;
use App\Models\User;
use App\Support\PreguntasFrecuentes;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Preguntas frecuentes y contacto con la administración.
 *
 * Vive dentro del panel: escribir exige sesión iniciada, así que el nombre y el correo
 * salen de la cuenta y no se piden de nuevo. El mensaje se guarda en la bandeja que la
 * administración lee (ver [Admin\Mensajes]); no depende del correo para no perderse.
 */
class Ayuda extends Component
{
    public string $motivo = '';

    public string $mensaje = '';

    /** Pregunta abierta en el acordeón; null = todas cerradas. */
    public ?int $abierta = null;

    public function mount(): void
    {
        // Primer motivo como preselección: obliga a menos clics y sigue siendo explícito
        // porque el desplegable muestra cuál está elegido.
        $this->motivo = array_key_first(MensajeContacto::MOTIVOS);
    }

    /** Abre una pregunta y cierra la que estuviera abierta. */
    public function alternar(int $indice): void
    {
        $this->abierta = $this->abierta === $indice ? null : $indice;
    }

    public function enviar(): void
    {
        $usuario = auth()->user();

        abort_if($usuario === null, 403);

        $validado = $this->validate([
            'motivo' => ['required', Rule::in(array_keys(MensajeContacto::MOTIVOS))],
            'mensaje' => ['required', 'string', 'min:20', 'max:3000'],
        ], attributes: [
            'motivo' => 'motivo',
            'mensaje' => 'mensaje',
        ]);

        $mensaje = MensajeContacto::query()->create([
            'user_id' => $usuario->id,
            'motivo' => $validado['motivo'],
            // Copia del contacto al momento de escribir: si la cuenta cambia de correo o
            // se elimina, la administración conserva a quién responderle.
            'nombre' => $usuario->name,
            'email' => $usuario->email,
            'mensaje' => trim($validado['mensaje']),
        ]);

        $this->avisarPorCorreo($mensaje);

        $this->reset('mensaje');
        $this->motivo = array_key_first(MensajeContacto::MOTIVOS);

        session()->flash('status', 'Recibimos tu mensaje. Te responderemos al correo de tu cuenta.');
    }

    /**
     * Avisa por correo del mensaje recién guardado.
     *
     * Nunca hace fracasar el envío del formulario: el mensaje YA está en la bandeja, que
     * es la fuente de verdad, y el correo es solo el aviso para no depender de que
     * alguien entre a mirar. Si el servidor de correo está caído, se deja constancia en
     * el log y la persona igual recibe su confirmación en pantalla; lo contrario sería
     * mostrarle un error por algo que sí se guardó.
     */
    private function avisarPorCorreo(MensajeContacto $mensaje): void
    {
        $destinatarios = $this->destinatarios($mensaje);

        if ($destinatarios === []) {
            Log::warning('Mensaje de contacto sin destinatarios de correo.', [
                'mensaje_id' => $mensaje->id,
                'motivo' => $mensaje->motivo,
            ]);

            return;
        }

        try {
            Mail::to($destinatarios)->send(new MensajeContactoRecibido($mensaje));
        } catch (\Throwable $e) {
            Log::error('No se pudo avisar por correo de un mensaje de contacto.', [
                'mensaje_id' => $mensaje->id,
                'motivo' => $mensaje->motivo,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * A quién le llega el aviso, según el motivo.
     *
     * El soporte técnico tiene casilla propia y va solo ahí. Todo lo demás —consultas de
     * servicios y otras— va a las cuentas de administración, en plural: la bandeja ya
     * está modelada como una sola para toda la administración (el estado de un mensaje no
     * es de cada admin), así que el aviso sigue el mismo criterio y no depende de que una
     * persona concreta siga teniendo cuenta.
     *
     * @return list<string>
     */
    private function destinatarios(MensajeContacto $mensaje): array
    {
        if ($mensaje->motivo === 'soporte') {
            $soporte = config('ad50.contacto.soporte');

            return is_string($soporte) && $soporte !== '' ? [$soporte] : [];
        }

        $administracion = User::query()
            ->whereIn('role', ['admin', 'superadmin'])
            ->orderBy('id')
            ->get(['id', 'email'])
            ->all();

        return array_values(array_map(
            static fn (User $admin): string => $admin->email,
            $administracion,
        ));
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'mensaje.min' => 'Cuéntanos un poco más: con al menos 20 caracteres podemos entender qué necesitas.',
        ];
    }

    #[Title('Ayuda y contacto · AD+50')]
    #[Layout('components.layouts.app')]
    public function render(): View
    {
        return view('livewire.ayuda', [
            'preguntas' => PreguntasFrecuentes::para(auth()->user()?->role),
            'motivos' => MensajeContacto::MOTIVOS,
        ]);
    }
}
