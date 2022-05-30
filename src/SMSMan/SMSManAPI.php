<?php

declare(strict_types=1);

namespace Hsldymq\SMS\SMSMan;

use Hsldymq\SMS\SMSMan\Exception\APIError;
use GuzzleHttp\Client;

class SMSManAPI
{
    private $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function rejectNumber(string $requestID)
    {
        $url = "http://api.sms-man.com/control/set-status?token={$this->token}&request_id={$requestID}&status=reject";
        $data = $this->doRequest($url);
        if (!($data['success'] ?? false)) {
            throw new \Exception(json_encode($data));
        }
    }

    public function getNumber(string $appID, string $countryID): array
    {
        $url = "http://api.sms-man.com/control/get-number?token={$this->token}&country_id={$countryID}&application_id={$appID}";
        $data = $this->doRequest($url);
        $requestID = $data['request_id'];
        $number = $data['number'];

        return [$number, $requestID];
    }

    public function getNumberLimitsByCountryID(string $countryID, ?string $orderBy = null): array
    {
        $result = [];
        $data = $this->getNumberLimits('', $countryID);
        foreach ($data as $each) {
            $result[$each['application_id']] = ['appID' => strval($each['application_id']), 'numbers' => $each['numbers']];
        }

        $orderBy = $orderBy !== null ? strtolower($orderBy) : null;
        if ($orderBy === 'asc') {
            uasort($result, fn($a, $b) => $a['numbers'] <=> $b['numbers']);
        } else if ($orderBy === 'desc') {
            uasort($result, fn($a, $b) => $b['numbers'] <=> $a['numbers']);
        }

        return array_values($result);
    }

    public function getNumberLimit(string $appID, string $countryID): int
    {
        $data = $this->getNumberLimits($appID, $countryID);

        return intval($data[$appID]['numbers']);
    }

    public function getNumberLimits(string $appID = '', string $countryID = ''): array
    {
        $url = "http://api.sms-man.com/control/limits?token={$this->token}";
        if ($appID) {
            $url .= "&application_id={$appID}";
        }
        if ($countryID) {
            $url .= "&country_id={$countryID}";
        }

        return $this->doRequest($url);
    }

    public function getTotalNumbersForCountries(): array
    {
        $result = [];
        foreach ($this->getNumberLimits() as $countries) {
            foreach ($countries as $each) {
                $countryID = $each['country_id'];
                if (!isset($result[$countryID])) {
                    $result[$countryID] = [
                        'countryCode' => $this->getCodeByID($countryID, true),
                        'numbers' => 0,
                    ];
                }
                $result[$countryID]['numbers'] += $each['numbers'];

            }
        }
        uasort($result, fn($a, $b) => $b <=> $a);

        return $result;
    }

    public function getAllCountryIDs(): array
    {
        $countries = $this->tryFetchCountries();

        return array_keys($countries);
    }

    public function getCodeByID(string $id, bool $throw): string
    {
        $countries = $this->tryFetchCountries();
        if (!isset($countries[$id]) && $throw) {
            throw new \InvalidArgumentException('no such country id found');
        }

        return $countries[$id] ?? '';
    }

    private function tryFetchCountries(): array
    {
        static $countries = null;

        if ($countries === null) {
            $countries = [];
            $data = $this->doRequest("http://api.sms-man.com/control/countries?token={$this->token}");
            foreach ($data as $each) {
                $countries[$each['id']] = $each['code'];
            }
        }

        return $countries;
    }

    private function doRequest(string $url): array
    {
        $client = new Client();
        $resp = $client->get($url);

        $data = json_decode(strval($resp->getBody()), true, 1024, JSON_THROW_ON_ERROR);
        if (isset($data['error_code'])) {
            throw new APIError($data['error_code']);
        }

        return $data;
    }
}