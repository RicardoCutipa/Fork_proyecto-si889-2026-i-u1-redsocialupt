<?php

namespace App\Services;

class ModerationService
{
    private const DICTIONARY_PATH = 'app/Support/Moderation/profanity-es-hispano.txt';

    /** @var array<string, bool>|null */
    private static ?array $dictionary = null;

    /** @var string[]|null */
    private static ?array $phrases = null;

    public function ensureClean(?string $text, string $contentType = 'contenido'): void
    {
        if (!$this->hasForbiddenLanguage($text)) {
            return;
        }

        $messages = [
            'post' => 'Tu publicacion contiene lenguaje no permitido.',
            'comment' => 'Tu comentario contiene lenguaje no permitido.',
            'message' => 'Tu mensaje contiene lenguaje no permitido.',
            'content' => 'El contenido contiene lenguaje no permitido.',
        ];

        throw new \InvalidArgumentException($messages[$contentType] ?? $messages['content'], 422);
    }

    public function hasForbiddenLanguage(?string $text): bool
    {
        $normalized = $this->normalizeText($text);
        if ($normalized === '') {
            return false;
        }

        $dictionary = $this->loadDictionary();
        $tokens = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($tokens as $token) {
            if (isset($dictionary[$token])) {
                return true;
            }
        }

        $haystack = ' ' . $normalized . ' ';
        foreach ($this->loadPhrases() as $phrase) {
            if (str_contains($haystack, ' ' . $phrase . ' ')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, bool>
     */
    private function loadDictionary(): array
    {
        if (self::$dictionary !== null) {
            return self::$dictionary;
        }

        $path = app()->basePath(self::DICTIONARY_PATH);
        if (!is_file($path)) {
            self::$dictionary = [];
            self::$phrases = [];
            return self::$dictionary;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $dictionary = [];
        $phrases = [];

        foreach ($lines as $line) {
            $normalized = $this->normalizeText($line);
            if ($normalized === '') {
                continue;
            }

            $dictionary[$normalized] = true;
            if (str_contains($normalized, ' ')) {
                $phrases[] = $normalized;
            }
        }

        usort($phrases, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

        self::$dictionary = $dictionary;
        self::$phrases = $phrases;

        return self::$dictionary;
    }

    /**
     * @return string[]
     */
    private function loadPhrases(): array
    {
        $this->loadDictionary();
        return self::$phrases ?? [];
    }

    private function normalizeText(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $value = trim((string) $text);
        if ($value === '') {
            return '';
        }

        $value = mb_strtolower($value, 'UTF-8');
        $value = strtr($value, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);

        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
