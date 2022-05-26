<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Hsldymq\SMS\SMSMan\SMSManAPI;

(new class {
    private string $token = 'w3FbfzHdttzCcpCV44SbWBtvUvNQSFjS';

    private SMSManAPI $api;

    public function run()
    {
        $this->api = new SMSManAPI($this->token);
        $data = $this->api->getTotalNumbersForCountries();

        echo json_encode($data, JSON_PRETTY_PRINT).PHP_EOL;
    }
})->run();