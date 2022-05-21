<?php

declare(strict_types=1);

namespace Hsldymq\SMS\SMSMan;

/**
 * @see https://sms-man.com/cn/site/docs-apiv2 "Get all countries"
 */
class Country
{
    private ?array $countries = null;

    private string $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function getAllCountryIDs(): array
    {
        $this->tryFetchCountries();

        return array_keys($this->countries);
    }

    public function getCodeByID(string $id, bool $throw): string
    {
        $this->tryFetchCountries();

        if (!isset($this->countries[$id]) && $throw) {
            throw new \InvalidArgumentException('no such country id found');
        }

        return $this->countries[$id] ?? '';
    }

    private function tryFetchCountries()
    {
        if ($this->countries) {
            return;
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->get("http://api.sms-man.com/control/countries?token={$this->token}");
        $data = json_decode(strval($response->getBody()), true, 1024, JSON_THROW_ON_ERROR);
        $this->countries = [];
        foreach ($data as $each) {
            $this->countries[$each['id']] = $each['code'];
        }
    }
}