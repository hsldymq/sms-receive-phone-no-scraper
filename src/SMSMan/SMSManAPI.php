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
                $result[$each['country_id']] = ($result[$each['country_id']] ?? 0) + $each['numbers'];
            }
        }
        uasort($result, fn($a, $b) => $b <=> $a);

        return $result;
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