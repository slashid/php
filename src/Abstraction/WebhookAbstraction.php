<?php

namespace SlashId\Php\Abstraction;

class WebhookAbstraction extends AbstractionBase
{
    public function findAll(): array
    {
        return $this->sdk->get('/organizations/webhooks');
    }

    public function findById(string $id): array
    {
        return $this->sdk->get('/organizations/webhooks/' . $id);
    }

    public function findByUrl(string $url): ?array
    {
        $webhooks = $this->findAll();
        foreach ($webhooks as $webhook) {
            if ($webhook['target_id'] ?? null === $url) {
                return $webhook;
            }
        }

        return null;
    }

    public function register(string $url, string $name, array $triggers, array $options): void
    {
        $payload = [
            'target_url' => $url,
            'name' => $name,
        ] + $options;

        if ($webhook = $this->findByUrl($url)) {
            $this->sdk->patch('/organizations/webhooks/' . $webhook['id'], $payload);
        } else {
            $this->sdk->post('/organizations/webhooks', $payload);
        }
    }

    public function deleteById(string $id): void
    {
        $this->sdk->delete('/organizations/webhooks/' . $id);
    }

    public function deleteByUrl(string $url): void
    {
        if ($webhook = $this->findByUrl($url)) {
            $this->deleteById($webhook['id']);
        }

        // @todo Create custom Exceptions.
        throw new \Exception(
            'There is no webhook in organization ' . $this->sdk->getOrganizationId() .
            ' for the URL "' . $url . '".'
        );
    }
}
