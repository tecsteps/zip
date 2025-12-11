<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class OpenRouterException extends Exception
{
    /**
     * Create a new exception for API errors.
     */
    public static function apiError(string $message, int $statusCode = 0): self
    {
        return new self("OpenRouter API error: {$message}", $statusCode);
    }

    /**
     * Create a new exception for invalid response format.
     */
    public static function invalidResponse(string $message = 'Invalid response format'): self
    {
        return new self("OpenRouter response error: {$message}");
    }

    /**
     * Create a new exception for missing configuration.
     */
    public static function missingConfiguration(string $key): self
    {
        return new self("OpenRouter configuration missing: {$key}");
    }
}
