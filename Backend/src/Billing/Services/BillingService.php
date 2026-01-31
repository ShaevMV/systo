<?php

declare(strict_types=1);

namespace Tickets\Billing\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tickets\Billing\DTO\PaymentRequestDTO;
use Tickets\Billing\DTO\PaymentResponseDTO;
use Tickets\Billing\ValueObject\DeviceValueObject;

class BillingService
{
    private string $login;
    private string $password;
    private string $host;

    public function __construct()
    {
        $this->login = env('BILLING_KEY_CLIENT');
        $this->password = env('BILLING_KEY_PASSWORD');
        $this->host = env('BILLING_HOST');

    }

    /**
     * Cоздать платежа
     *
     * @throws RequestException
     */
    public function createPayments(
        PaymentRequestDTO $requestDTO,
        DeviceValueObject $deviceValueObject,
    ): PaymentResponseDTO
    {
        $response = Http::withBasicAuth(
            $this->login,
            $this->password,
        )->post(
            $this->host,
            $requestDTO->toArray(),
        );

        if ($response->failed()) {
            $response->throw();
        }

        $link = match (true) {
            $deviceValueObject->isAndroid() => $response['data']['payment_qr_urls']['android'],
            $deviceValueObject->isIOS() => $response['data']['payment_qr_urls']['ios'],
            default => $response['data']['payment_qr_urls']['desktop'],
        };

        return new PaymentResponseDTO(
            $link,
            $response['data']['status'],
            $response['data']['status_code_error'],
        );
    }
}
