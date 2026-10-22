<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Charges a mobile wallet through the external payment API.
 * The API receives the token as a Bearer header, plus amount and phone_number
 * (9 digits, starting with 84 or 85) in the JSON body.
 */
class MobilePaymentGateway
{
    /** Valid local number: 84xxxxxxx or 85xxxxxxx. */
    public const PHONE_PATTERN = '/^8[45]\d{7}$/';

    public function isConfigured(): bool
    {
        return filled(config('services.mobile_payments.url')) && filled(config('services.mobile_payments.token'));
    }

    /** Accepts "84 123 4567", "+258841234567" or "258841234567" and returns "841234567". */
    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) === 12 && str_starts_with($digits, '258')) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }

    public static function isValidPhone(?string $phone): bool
    {
        return (bool) preg_match(self::PHONE_PATTERN, self::normalizePhone($phone));
    }

    public function charge(string $phone, int|float $amount): PaymentResult
    {
        if (! $this->isConfigured()) {
            return new PaymentResult(false, message: 'O método de pagamento ainda não está configurado. Contacte o suporte.');
        }

        $phone = self::normalizePhone($phone);
        if (! self::isValidPhone($phone)) {
            return new PaymentResult(false, message: 'Número inválido. Use 9 dígitos começando por 84 ou 85.');
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken(config('services.mobile_payments.token'))
                ->timeout((int) config('services.mobile_payments.timeout', 90))
                ->post(config('services.mobile_payments.url'), [
                    'amount' => $amount,
                    'phone_number' => $phone,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Mobile payment connection failed', ['error' => $e->getMessage()]);

            return new PaymentResult(false, message: 'Não foi possível contactar o serviço de pagamento. Tente novamente.');
        }

        $body = $response->json();
        $body = is_array($body) ? $body : ['raw' => mb_substr($response->body(), 0, 1000)];
        $successful = $response->successful() && $this->bodyIndicatesSuccess($body);

        if (! $successful) {
            Log::info('Mobile payment refused', ['status' => $response->status(), 'body' => $body]);
        }

        return new PaymentResult(
            $successful,
            $this->firstString($body, ['transaction_id', 'transactionId', 'reference', 'transaction_reference', 'output_TransactionID', 'conversation_id', 'output_ConversationID', 'data.transaction_id', 'data.reference', 'id']),
            $this->firstString($body, ['message', 'detail', 'detail.0.msg', 'error', 'output_ResponseDesc', 'data.message', 'errors.0'])
                ?? ($successful ? null : 'O pagamento não foi concluído (código '.$response->status().').'),
            $body,
        );
    }

    /** The API may answer 200 with a failure flag; respect it when present. */
    private function bodyIndicatesSuccess(array $body): bool
    {
        if (array_key_exists('success', $body)) {
            return filter_var($body['success'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($body['status']) && is_string($body['status'])) {
            return in_array(strtolower($body['status']), ['success', 'successful', 'succeeded', 'paid', 'completed', 'ok', 'approved'], true);
        }

        if (isset($body['output_ResponseCode'])) {
            return $body['output_ResponseCode'] === 'INS-0';
        }

        return true;
    }

    private function firstString(array $body, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($body, $key);
            if (is_scalar($value) && (string) $value !== '') {
                return mb_substr((string) $value, 0, 250);
            }
        }

        return null;
    }
}
