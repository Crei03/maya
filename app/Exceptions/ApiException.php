<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * Exception builder reutilizable para toda la aplicación MAYA.
 *
 * Proporciona una interfaz fluida para crear excepciones consistentes
 * con código de error, mensaje, estado HTTP y datos adicionales.
 *
 * @example
 * throw ApiException::make('USER_NOT_FOUND', 'Usuario no encontrado', 404);
 *
 * @example
 * throw ApiException::notFound('SHIPMENT_NOT_FOUND', 'Envío no existe');
 *
 * @example
 * throw ApiException::validation('VALIDATION_ERROR', 'Datos inválidos')
 *     ->withData(['field' => 'email']);
 */
class ApiException extends Exception
{
    /**
     * Código de error de la aplicación (ej: 'USER_NOT_FOUND').
     */
    protected string $errorCode;

    /**
     * Estado HTTP de la respuesta.
     */
    protected int $httpStatus;

    /**
     * Datos adicionales para la respuesta.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Constructor privado. Usar métodos estáticos factory.
     */
    private function __construct(
        string $errorCode,
        string $message,
        int $httpStatus = 500,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
    }

    /**
     * Crea una nueva instancia de ApiException.
     *
     * @param string $errorCode Código de error de la aplicación
     * @param string $message Mensaje descriptivo del error
     * @param int $httpStatus Código HTTP de estado
     */
    public static function make(
        string $errorCode,
        string $message,
        int $httpStatus = 500,
        ?Throwable $previous = null
    ): self {
        return new self($errorCode, $message, $httpStatus, $previous);
    }

    /**
     * Crea una excepción de recurso no encontrado (404).
     */
    public static function notFound(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Crea una excepción de error de validación (422).
     */
    public static function validation(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Crea una excepción de acceso no autorizado (403).
     */
    public static function forbidden(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Crea una excepción de no autenticado (401).
     */
    public static function unauthorized(string $errorCode = 'UNAUTHORIZED', string $message = 'No autenticado'): self
    {
        return new self($errorCode, $message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Crea una excepción de conflicto (409).
     */
    public static function conflict(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, Response::HTTP_CONFLICT);
    }

    /**
     * Crea una excepción de error del servidor (500).
     */
    public static function serverError(string $errorCode, string $message, ?Throwable $previous = null): self
    {
        return new self($errorCode, $message, Response::HTTP_INTERNAL_SERVER_ERROR, $previous);
    }

    /**
     * Crea una excepción de servicio no disponible (503).
     */
    public static function serviceUnavailable(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * Agrega datos adicionales a la excepción (fluent interface).
     *
     * @param array<string, mixed> $data
     */
    public function withData(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Agrega un dato individual (fluent interface).
     */
    public function with(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Obtiene el código de error de la aplicación.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Obtiene el estado HTTP.
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * Obtiene los datos adicionales.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Renderiza la excepción como respuesta JSON.
     *
     * Usado por Laravel cuando la excepción se lanza en una petición HTTP.
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
            ],
        ];

        if (!empty($this->data)) {
            $response['error']['data'] = $this->data;
        }

        // En modo debug, agregar información adicional
        if (config('app.debug')) {
            $response['debug'] = [
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => collect($this->getTrace())->map(fn ($item) => [
                    'file' => $item['file'] ?? null,
                    'line' => $item['line'] ?? null,
                    'function' => $item['function'] ?? null,
                ])->take(5)->toArray(),
            ];
        }

        return response()->json($response, $this->httpStatus);
    }

    /**
     * Reporta la excepción (para logging).
     *
     * Los errores 5xx se reportan a los logs, los 4xx no.
     */
    public function report(): bool
    {
        // No reportar errores del cliente (4xx)
        if ($this->httpStatus < 500) {
            return false;
        }

        // Los errores del servidor (5xx) sí se reportan
        return true;
    }
}
