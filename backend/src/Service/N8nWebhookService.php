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

    public function sendDispatch(array $contactData, array $metadata = []): array
    {
        try {
            $payload = [
                'contact' => $contactData,
                'metadata' => $metadata,
                'timestamp' => (new \DateTime())->format('c'),
            ];

            $response = $this->httpClient->request('POST', $this->webhookUrl, [
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

    public function sendBatch(array $contacts, array $metadata = []): array
    {
        $results = [];

        foreach ($contacts as $contact) {
            $results[] = $this->sendDispatch($contact, $metadata);

            // Small delay between requests to avoid overwhelming the webhook
            usleep(100000); // 100ms
        }

        return $results;
    }
}
