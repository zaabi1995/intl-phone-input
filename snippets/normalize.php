<?php
declare(strict_types=1);

/**
 * Normalize a user-supplied phone number to digits-only, country-code prefixed.
 *
 * Accepts:
 *   - Bare 8-digit Oman local number (starts with 7 or 9)  -> '968' + digits
 *   - Already-international form (E.164, +CC...) -> digits stripped, kept as-is
 *
 * Examples that all return '96894022553':
 *   '94022553' / '+968 9402 2553' / '00968-94022553' / '968 94022553'
 *
 * Examples for non-Oman:
 *   '+358 40 615 5262' -> '358406155262'
 *   '+1 (415) 555-1234' -> '14155551234'
 *
 * Returns null if input has fewer than 8 digits or more than 15 (E.164 max).
 *
 * Use the digits-only return value as the recipient for Dardasha WhatsApp
 * API or Twilio SMS (both want digits without the '+' prefix).
 */
function normalizePhone(string $raw): ?string {
  $digits = preg_replace('/\D+/', '', $raw);
  if ($digits === '' || $digits === null) return null;
  if (str_starts_with($digits, '00')) $digits = substr($digits, 2);

  // Bare 8-digit Oman local number -> add country code
  if (strlen($digits) === 8 && preg_match('/^[79]\d{7}$/', $digits)) {
    return '968' . $digits;
  }
  // E.164 international form (8-15 digits per ITU-T E.164)
  if (strlen($digits) >= 8 && strlen($digits) <= 15) {
    return $digits;
  }
  return null;
}
