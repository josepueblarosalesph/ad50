<?php

namespace App\Livewire;

use App\Models\MensajeContacto;
use App\Support\PreguntasFrecuentes;
use Illuminate\Contracts\View\View;
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

        MensajeContacto::query()->create([
            'user_id' => $usuario->id,
            'motivo' => $validado['motivo'],
            // Copia del contacto al momento de escribir: si la cuenta cambia de correo o
            // se elimina, la administración conserva a quién responderle.
            'nombre' => $usuario->name,
            'email' => $usuario->email,
            'mensaje' => trim($validado['mensaje']),
        ]);

        $this->reset('mensaje');
        $this->motivo = array_key_first(MensajeContacto::MOTIVOS);

        session()->flash('status', 'Recibimos tu mensaje. Te responderemos al correo de tu cuenta.');
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
