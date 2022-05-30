<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Hsldymq\SMS\SMSMan\SMSManAPI;

(new class {
    public function run()
    {
//        $this->genPhoneNosSQL();
        $this->genOriginsSQL();
    }

    private function genPhoneNosSQL(): void
    {
        echo "phone_no,calling_code,country_code_2";
        foreach ($this->iter() as $each) {
            $callingCode = $each[0];
            $phoneNo = $each[1];
            $countryCode = $each[2];

            echo "\n{$phoneNo},{$callingCode},{$countryCode}";
        }
    }

    private function genOriginsSQL(): void
    {
        echo "phone_no,calling_code,origin,type";
        foreach ($this->iter() as $each) {
            $callingCode = $each[0];
            $phoneNo = $each[1];

            echo "\n{$phoneNo},{$callingCode},sms-man.com,purchased";
        }
    }

    private function iter()
    {
        $dir = new DirectoryIterator(__DIR__.'/numbers');
        foreach ($dir as $each) {
            if ($each->isDot()) {
                continue;
            }

            $fp = fopen($each->getRealPath(), 'r');
            while ($row = fgetcsv($fp)) {
                yield $row;
            }
            fclose($fp);
        }
    }
})->run();