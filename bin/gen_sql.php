<?php

declare(strict_types=1);

use ChibiFR\CountryConverter\InvalidCountryNameException;

require __DIR__.'/../vendor/autoload.php';

(new class extends \ChibiFR\CountryConverter\Converter {
    private array $data;

    private string $origin = 'receive-sms-online.info';

    public function __construct()
    {
        $this->data = require __DIR__.'/data.php';

        parent::__construct();
    }

    public function run()
    {
        $this->v1();
    }

    /**
     * 输入二维数组:
     *  字段1: 手机号
     *  字段2: 国家名字
     *
     * @return void
     */
    public function v1()
    {
        $this->v1GenOriginSQL();
        $this->v1GenPhoneSQL('free');
    }

    private function v1GenOriginSQL()
    {
        echo "replace into phones_origin (calling_code, phone_no, origin) values ";
        $count = 0;
        foreach ($this->v1Iter() as $each) {
            $count++;
            $callingCode = $each['callingCode'];
            $phoneNo = $each['phoneNo'];
            if ($count !== 1) {
                echo ",";
            }
            echo "\n('{$callingCode}', '{$phoneNo}', '{$this->origin}')";
        }

        echo ";\n";
    }

    private function v1GenPhoneSQL(string $type)
    {
        echo "replace into phones (calling_code, phone_no, country_code_2, type) values ";
        $count = 0;
        foreach ($this->v1Iter() as $each) {
            $count++;
            $callingCode = $each['callingCode'];
            $phoneNo = $each['phoneNo'];
            $countryCode = $each['countryCode'];
            if ($count !== 1) {
                echo ",";
            }
            echo "\n('{$callingCode}', '{$phoneNo}', '{$countryCode}', '{$type}')";
        }
        echo ";\n";
    }

    private function v1Iter()
    {
        foreach ($this->data as $each) {
            $phoneNo = $each[0];
            $countryName = $this->canonicalizeCountryName($each[1]);

            $countryCode = $this->getCountryCode($countryName);
            $country = country($countryCode);
            foreach ($country->getCallingCodes() ?? [] as $callingCode) {
                $callingCodePlus = "+{$callingCode}";
                if (str_starts_with($phoneNo, $callingCodePlus)) {
                    $phoneNo = substr($phoneNo, strlen($callingCodePlus));
                    yield [
                        'callingCode' => $callingCode,
                        'phoneNo' => $phoneNo,
                        'countryCode' => $countryCode,
                    ];
                    continue 2;
                }
            }

            var_dump($country->getCallingCodes());
            throw new Exception("{$phoneNo}");
        }
    }

    private function canonicalizeCountryName(string $countryName): string
    {
        $c = strtolower($countryName);
        return [
            'south korea' => 'Korea, Republic of',
            'usa' => 'United States of America',
            'united states' => 'United States of America',
            'united kingdom' => 'United Kingdom of Great Britain and Northern Ireland',
            'france, french republic' => 'France',
            'portugal, portuguese republic' => 'Portugal',
            'vietnam' => 'Viet Nam',
            'korea' => 'Korea, Republic of',
            'switzerland, swiss confederation' => 'Switzerland',
            'russia' => 'Russian Federation',
        ][$c] ?? $countryName;
    }

    public function getCountryCode($countryName)
    {
        $code = array_search(strtolower($countryName), array_map('strtolower', $this->countries));
        if (!$code) {
            throw new InvalidCountryNameException("No ISO key found for the country $countryName");
        }

        return $code;
    }
})->run();