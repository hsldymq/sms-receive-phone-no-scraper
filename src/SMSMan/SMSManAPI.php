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

    public function getLimit(string $appID, string $countryID): int
    {
        $url = "http://api.sms-man.com/control/limits?token={$this->token}&country_id={$countryID}&application_id={$appID}";
        $data = $this->doRequest($url);

        return $data[$appID]['numbers'];
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