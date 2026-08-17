<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        // El aviso se anota después del logout a propósito: `Logout` invalida la sesión y
        // regenera su ID, así que cualquier flash puesto antes se perdería por el camino.
        // Por lo mismo la redirección es dura (sin `navigate`): la portada tiene que
        // cargarse con la cookie de la sesión nueva para poder leer el mensaje.
        // Llave propia y no el `status` genérico: las pantallas de Fortify (login, recuperar
        // contraseña) ya usan esa llave y mostrarían el aviso donde no corresponde.
        session()->flash('cuenta_eliminada', 'Tus datos han sido eliminados exitosamente.');

        $this->redirect(route('home'));
    }
}; ?>

<flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <div>
            <flux:heading size="lg">¿Seguro que quieres eliminar tu cuenta?</flux:heading>

            <flux:subheading>
                Al eliminar tu cuenta, toda su información se borra de forma permanente. Ingresa tu contraseña para confirmar que deseas eliminarla.
            </flux:subheading>
        </div>

        <flux:input wire:model="password" label="Contraseña" type="password" viewable />

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">Cancelar</flux:button>
            </flux:modal.close>

            <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                Eliminar cuenta
            </flux:button>
        </div>
    </form>
</flux:modal>
