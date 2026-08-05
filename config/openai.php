<?php
/**
 * DEPRECATED — do not use.
 *
 * The previous version of this file contained a live OpenAI API key
 * committed in plain text. That key must be revoked.
 *
 * It also contained a bug: the key string was used as the ARRAY INDEX
 * instead of the value, so OPENAI_API_KEY always resolved to an empty
 * string. The AI integration has never actually worked.
 *
 * AI drafting will be rebuilt properly on the new platform, reading its
 * credentials from .env and de-identifying inspection data before any
 * cross-border transfer (Law No. 058/2021).
 */

require_once __DIR__ . '/env.php';

define('AI_API_KEY', env('ANTHROPIC_API_KEY', ''));
define('AI_MODEL',   env('AI_MODEL', 'claude-sonnet-4-6'));
