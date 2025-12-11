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
You are a package damage assessment expert. Analyze this image of a damaged package and provide a structured assessment.

## Assessment Criteria

### severity (required)
- "minor": Superficial damage only (scuffs, small dents, minor tears). Packaging integrity maintained. Contents likely unaffected.
- "moderate": Visible structural damage (crushed corners, holes, significant tears). Packaging compromised. Contents possibly affected.
- "severe": Major structural failure (collapsed box, large holes, severe water damage). Contents likely damaged or destroyed.

### damage_type (required)
Identify the PRIMARY type of damage visible:
- "crushed": Compression damage, collapsed structure, flattened areas
- "wet": Water damage, staining, warping from moisture
- "torn": Ripped packaging, exposed contents
- "punctured": Holes from impact or sharp objects
- "burned": Fire or heat damage, scorch marks
- "contaminated": Spills, stains from foreign substances

### value_impact (required)
Estimate the impact on the contents' value:
- "low": Contents likely undamaged, package still protective (<10% value loss)
- "medium": Contents may have minor damage, some protection compromised (10-50% value loss)
- "high": Contents likely damaged, significant protection failure (50-90% value loss)
- "total_loss": Contents destroyed or unsalvageable (>90% value loss)

### liability (required)
Determine the most likely responsible party based on damage characteristics:
- "carrier": Damage consistent with shipping/handling (impact damage, crushing from stacking, weather exposure during transit)
- "sender": Damage suggests inadequate packaging (insufficient padding, wrong box size, fragile items not marked)
- "recipient": Damage appears to have occurred after delivery (opened package, delayed reporting)
- "unknown": Insufficient evidence to determine responsibility

## Response Format
Respond ONLY with valid JSON containing these four fields. No explanations or additional text.

Example: {"severity": "moderate", "damage_type": "crushed", "value_impact": "medium", "liability": "carrier"}
PROMPT;

    private const DEFAULT_BASE_URL = 'https://openrouter.ai/api/v1';

    private const DEFAULT_MODEL = 'anthropic/claude-sonnet-4';

    private const STORAGE_DISK = 'public';

    private string $apiKey;

    private string $baseUrl;

    private string $model;

    /**
     * Create a new OpenRouterService instance.
     *
     * Configuration can be injected directly for testing, or defaults from config will be used.
     *
     * @throws OpenRouterException
     */
    public function __construct(
        ?string $apiKey = null,
        ?string $baseUrl = null,
        ?string $model = null,
    ) {
        $this->apiKey = $apiKey ?? config('services.openrouter.api_key') ?? '';
        $this->baseUrl = $baseUrl ?? config('services.openrouter.base_url') ?? self::DEFAULT_BASE_URL;
        $this->model = $model ?? config('services.openrouter.model') ?? self::DEFAULT_MODEL;

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
        if (! Storage::disk(self::STORAGE_DISK)->exists($imagePath)) {
            throw OpenRouterException::invalidResponse("Image not found at path: {$imagePath}");
        }

        $imageContent = Storage::disk(self::STORAGE_DISK)->get($imagePath);
        $mimeType = Storage::disk(self::STORAGE_DISK)->mimeType($imagePath);

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
