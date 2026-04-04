<?php

declare(strict_types=1);

namespace App\Utils;

use App\Config\AnalysisConfig;

/**
 * Minimal JWT encoder/decoder for HS256 access tokens.
 *
 * Keeps JWT logic in one place so the auth service and middleware do not have
 * to deal with base64url encoding, signing, and claim validation directly.
 */
class JwtService
{
    private const ALGORITHM = 'HS256';
    private const TOKEN_TYPE = 'JWT';

    /**
     * Issue a signed access token and return the token plus metadata needed
     * for server-side session tracking.
     *
     * @return array{token: string, jti: string, expiresAt: string}
     */
    public function issueAccessToken(int $userId): array
    {
        $issuedAt = time();
        $expiresAtTimestamp = $issuedAt + (AnalysisConfig::TOKEN_EXPIRY_HOURS * 3600);
        $jti = bin2hex(random_bytes(AnalysisConfig::TOKEN_RANDOM_BYTES));

        $header = [
            'alg' => self::ALGORITHM,
            'typ' => self::TOKEN_TYPE,
        ];

        $payload = [
            'iss' => $this->issuer(),
            'sub' => (string) $userId,
            'jti' => $jti,
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $expiresAtTimestamp,
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signingInput = "{$encodedHeader}.{$encodedPayload}";
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $signingInput, $this->secret(), true));

        return [
            'token' => "{$signingInput}.{$signature}",
            'jti' => $jti,
            'expiresAt' => gmdate('Y-m-d H:i:s', $expiresAtTimestamp),
        ];
    }

    /**
     * Decode and verify an access token, returning its claims if valid.
     *
     * @return array<string, mixed>
     */
    public function decodeAccessToken(string $token): array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new \RuntimeException('Invalid JWT format', 401);
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;
        $header = $this->decodeJsonSegment($encodedHeader);
        $payload = $this->decodeJsonSegment($encodedPayload);

        if (($header['alg'] ?? null) !== self::ALGORITHM || ($header['typ'] ?? null) !== self::TOKEN_TYPE) {
            throw new \RuntimeException('Invalid JWT header', 401);
        }

        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$encodedHeader}.{$encodedPayload}", $this->secret(), true)
        );

        if (!hash_equals($expectedSignature, $encodedSignature)) {
            throw new \RuntimeException('Invalid JWT signature', 401);
        }

        $this->validateClaims($payload);

        return $payload;
    }

    /** Decode a base64url JSON segment into an associative array. */
    private function decodeJsonSegment(string $segment): array
    {
        $decoded = $this->base64UrlDecode($segment);
        $data = json_decode($decoded, true);

        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JWT payload', 401);
        }

        return $data;
    }

    /** Validate the standard claims we rely on for access tokens. */
    private function validateClaims(array $payload): void
    {
        $issuedAt = $payload['iat'] ?? null;
        $notBefore = $payload['nbf'] ?? null;
        $expiresAt = $payload['exp'] ?? null;
        $subject = $payload['sub'] ?? null;
        $jwtId = $payload['jti'] ?? null;
        $issuer = $payload['iss'] ?? null;
        $now = time();

        $claimsAreValid = is_int($issuedAt)
            && is_int($notBefore)
            && is_int($expiresAt)
            && is_string($subject)
            && ctype_digit($subject)
            && is_string($jwtId)
            && $jwtId !== ''
            && is_string($issuer)
            && $issuer === $this->issuer();

        if (!$claimsAreValid) {
            throw new \RuntimeException('Invalid JWT claims', 401);
        }

        if ($notBefore > $now || $expiresAt <= $now) {
            throw new \RuntimeException('JWT expired or not yet valid', 401);
        }
    }

    /** Base64url-encode binary or string input without padding. */
    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** Base64url-decode a JWT segment. */
    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new \RuntimeException('Invalid JWT encoding', 401);
        }

        return $decoded;
    }

    /** Resolve the signing secret from the environment. */
    private function secret(): string
    {
        $secret = getenv('JWT_SECRET') ?: 'visual-shield-local-dev-secret';

        if ($secret === '') {
            throw new \RuntimeException('Missing JWT secret', 500);
        }

        return $secret;
    }

    /** Resolve the issuer claim for locally generated tokens. */
    private function issuer(): string
    {
        return getenv('JWT_ISSUER') ?: 'visual-shield';
    }
}
