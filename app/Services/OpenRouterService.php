<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\OpenRouterException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OpenRouterService
{
    private const ANALYSIS_PROMPT = <<<'PROMPT'
Analyze this image of a damaged package. Provide a JSON assessment with:
- severity: "minor", "moderate", or "severe"
- damage_type: type of damage (e.g., "crushed", "wet", "torn", "punctured")
- value_impact: "low", "medium", "high", or "total_loss"
- liability: "carrier", "sender", "recipient", or "unknown"

Respond ONLY with valid JSON, no other text.
PROMPT;

    private string $apiKey;

    private string $baseUrl;

    private string $model;

    /**
     * Create a new OpenRouterService instance.
     *
     * @throws OpenRouterException
     */
    public function __construct()
    {
        $this->apiKey = config('services.openrouter.api_key') ?? '';
        $this->baseUrl = config('services.openrouter.base_url') ?? 'https://openrouter.ai/api/v1';
        $this->model = config('services.openrouter.model') ?? 'anthropic/claude-sonnet-4';

        if (empty($this->apiKey)) {
            throw OpenRouterException::missingConfiguration('api_key');
        }
    }

    /**
     * Analyze a damage photo using AI vision capabilities.
     *
     * @return array{severity: string, damage_type: string, value_impact: string, liability: string}
     *
     * @throws OpenRouterException
     */
    public function analyzeDamagePhoto(string $imagePath): array
    {
        $imageData = $this->readImageAsBase64($imagePath);

        $response = $this->createHttpClient()
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => self::ANALYSIS_PROMPT,
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $imageData,
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 500,
            ]);

        if ($response->failed()) {
            $errorMessage = $response->json('error.message') ?? 'Unknown error';
            throw OpenRouterException::apiError($errorMessage, $response->status());
        }

        return $this->parseResponse($response->json());
    }

    /**
     * Create an HTTP client with authentication headers.
     */
    private function createHttpClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(60);
    }

    /**
     * Read an image from storage and convert to base64 data URI.
     *
     * @throws OpenRouterException
     */
    private function readImageAsBase64(string $imagePath): string
    {
        if (! Storage::disk('public')->exists($imagePath)) {
            throw OpenRouterException::invalidResponse("Image not found at path: {$imagePath}");
        }

        $imageContent = Storage::disk('public')->get($imagePath);
        $mimeType = Storage::disk('public')->mimeType($imagePath);

        if ($imageContent === null) {
            throw OpenRouterException::invalidResponse("Could not read image at path: {$imagePath}");
        }

        $base64 = base64_encode($imageContent);

        return "data:{$mimeType};base64,{$base64}";
    }

    /**
     * Parse the API response and extract analysis data.
     *
     * @param  array<string, mixed>|null  $responseData
     * @return array{severity: string, damage_type: string, value_impact: string, liability: string}
     *
     * @throws OpenRouterException
     */
    private function parseResponse(?array $responseData): array
    {
        if (! $responseData) {
            throw OpenRouterException::invalidResponse('Empty response');
        }

        $content = $responseData['choices'][0]['message']['content'] ?? null;

        if (! $content) {
            throw OpenRouterException::invalidResponse('No content in response');
        }

        $jsonContent = trim($content);

        if (str_starts_with($jsonContent, '```json')) {
            $jsonContent = substr($jsonContent, 7);
        }
        if (str_starts_with($jsonContent, '```')) {
            $jsonContent = substr($jsonContent, 3);
        }
        if (str_ends_with($jsonContent, '```')) {
            $jsonContent = substr($jsonContent, 0, -3);
        }

        $jsonContent = trim($jsonContent);

        $parsed = json_decode($jsonContent, true);

        if (! is_array($parsed)) {
            throw OpenRouterException::invalidResponse('Response is not valid JSON');
        }

        $requiredKeys = ['severity', 'damage_type', 'value_impact', 'liability'];
        foreach ($requiredKeys as $key) {
            if (! isset($parsed[$key])) {
                throw OpenRouterException::invalidResponse("Missing required field: {$key}");
            }
        }

        return [
            'severity' => (string) $parsed['severity'],
            'damage_type' => (string) $parsed['damage_type'],
            'value_impact' => (string) $parsed['value_impact'],
            'liability' => (string) $parsed['liability'],
        ];
    }
}
