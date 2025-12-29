<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class N8nWebhookService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $webhookUrl
    ) {
    }

    public function sendDispatch(array $contactData, array $metadata = [], ?string $customWebhookUrl = null): array
    {
        $webhookUrl = $customWebhookUrl ?? $this->webhookUrl;

        if (empty($webhookUrl)) {
            return [
                'success' => false,
                'error' => 'No webhook URL configured',
            ];
        }

        try {
            $payload = [
                'contact' => $contactData,
                'metadata' => $metadata,
                'timestamp' => (new \DateTime())->format('c'),
            ];

            $response = $this->httpClient->request('POST', $webhookUrl, [
                'json' => $payload,
                'timeout' => 30,
            ]);

            return [
                'success' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
                'status_code' => $response->getStatusCode(),
                'response' => $response->toArray(false),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendBatch(array $contacts, array $metadata = [], ?string $customWebhookUrl = null): array
    {
        $results = [];

        foreach ($contacts as $contact) {
            $results[] = $this->sendDispatch($contact, $metadata, $customWebhookUrl);

            // Small delay between requests to avoid overwhelming the webhook
            usleep(100000); // 100ms
        }

        return $results;
    }
}
