<?php

namespace App\Http\Requests\Auth;

use App\Mail\FailedLoginAlert;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoginRequest extends FormRequest
{
    /** Tres errores bloquean la cuenta durante cinco minutos. */
    private const MAX_ATTEMPTS = 3;

    private const DECAY_SECONDS = 300;

    /**
     * Normaliza el correo antes de autenticarlo.
     * Se conecta con users.email y evita rechazos por espacios o mayúsculas accidentales.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->email)),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            if (RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
                $this->sendFailedLoginAlert();
                $this->throwLockoutValidationException();
            }

            throw ValidationException::withMessages([
                // El mensaje aparece debajo de la contraseña sin confirmar si el correo existe.
                'password' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        $this->throwLockoutValidationException();
    }

    /**
     * Muestra el tiempo restante debajo del campo password y emite el evento de bloqueo.
     *
     * @throws ValidationException
     */
    private function throwLockoutValidationException(): never
    {
        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'password' => 'Demasiados intentos incorrectos. Vuelve a intentarlo en '.max(1, (int) ceil($seconds / 60)).' minutos.',
        ]);
    }

    /**
     * Avisa al correo de contacto al producirse el tercer error de contraseña.
     * Si SMTP falla, conserva el bloqueo y registra el problema sin exponer credenciales.
     */
    private function sendFailedLoginAlert(): void
    {
        $user = User::query()->whereRaw('LOWER(email) = ?', [Str::lower((string) $this->email)])->first();

        if (! $user) {
            return;
        }

        $recipient = $user->correo_contacto ?: $user->email;

        try {
            Mail::to($recipient)->send(new FailedLoginAlert(
                loginEmail: $user->email,
                ipAddress: (string) ($this->ip() ?: 'No disponible'),
                occurredAt: now(),
            ));
        } catch (Throwable $exception) {
            Log::warning('No se pudo enviar la alerta de intentos fallidos de acceso.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        // La llave por cuenta impide evadir el bloqueo cambiando de IP y no guarda el correo en texto plano.
        return 'login|'.hash('sha256', Str::transliterate(Str::lower($this->string('email'))));
    }
}
