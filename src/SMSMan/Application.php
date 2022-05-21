<?php

declare(strict_types=1);

namespace Hsldymq\SMS\SMSMan;

/**
 * @see https://sms-man.com/cn/site/docs-apiv2 "Get all services"
 */
class Application
{
    private ?array $apps = null;

    private string $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function getAllApplicationIDs(): array
    {
        $this->tryFetchApps();

        return array_keys($this->apps);
    }

    public function getCodeByID(string $id, bool $throw): string
    {
        $this->tryFetchApps();

        if (!isset($this->apps[$id]) && $throw) {
            throw new \InvalidArgumentException('no such application id found');
        }

        return $this->apps[$id] ?? '';
    }

    private function tryFetchApps()
    {
        if ($this->apps) {
            return;
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->get("http://api.sms-man.com/control/applications?token={$this->token}");

        $data = json_decode(strval($response->getBody()), true, 1024, JSON_THROW_ON_ERROR);
        $this->apps = [];
        foreach ($data as $each) {
            $this->apps[$each['id']] = [
                'title' => $each['title'],
                'code' => $each['code'],
            ];
        }
    }
}