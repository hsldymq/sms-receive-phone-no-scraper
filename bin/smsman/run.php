<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Hsldymq\SMS\SMSMan\Country;
use Hsldymq\SMS\SMSMan\Application;
use Hsldymq\SMS\SMSMan\SMSManAPI;
use Hsldymq\SMS\SMSMan\Exception\ReleaseNumberError;
use Hsldymq\SMS\SMSMan\Exception\APIError;
use Hsldymq\SMS\SMSMan\Exception\AlreadyFetchedNumberException;

(new class{
    private string $token = 'w3FbfzHdttzCcpCV44SbWBtvUvNQSFjS';

    private Country $country;
    private Application $application;
    private SMSManAPI $api;

    private $rejectErrCount = 0;
    private $rejectErrorLimit = 10;

    public function __construct()
    {
        $this->api = new SMSManAPI($this->token);
        $this->country = new Country($this->token);
        $this->application = new Application($this->token);
    }

    public function run()
    {
        $countryIDs = $this->country->getAllCountryIDs();
        $appIDs = $this->application->getAllApplicationIDs();

        $count = 0;
        foreach ($appIDs as $appID) {
            foreach ($countryIDs as $countryID) {
                tryAgain:
                $remainNum = $this->api->getLimit($appID, $countryID);
                $numbers = $this->readRestoredNumbers($appID, $countryID);

                ensureAll:
                while (count($numbers) < $remainNum * 0.8) {
                    echo "$count: $appID - $countryID ... ";
                    try {
                        $this->fetchAndRelease($appID, $countryID, $numbers);
                    } catch (APIError $e) {
                        echo "{$e->getMessage()} (".count($numbers)."/{$remainNum})".PHP_EOL;
                        goto tryAgain;
                    } catch (AlreadyFetchedNumberException $e) {
                        echo "already fetched (".count($numbers)."/{$remainNum})".PHP_EOL;
                        continue;
                    } catch (ReleaseNumberError $e) {
                        $this->rejectErrCount++;
                        if ($this->rejectErrCount > $this->rejectErrorLimit) {
                            throw $e;
                        }
                        continue;
                    } catch (\Throwable $e) {
                        echo "error: {$e->getMessage()} (".count($numbers)."/{$remainNum})".PHP_EOL;
                        continue;
                    }
                    $count++;
                    echo "fetched (".count($numbers)."/{$remainNum})".PHP_EOL;
                }

                $remainNum = $this->api->getLimit($appID, $countryID);
                if (count($numbers) < $remainNum * 0.8) {
                    goto ensureAll;
                }
            }
        }
    }

    private function fetchAndRelease(string $appID, string $countryID, array &$numbers)
    {
        $fetchedNew = false;

        list($number, $requestID) = $this->api->getNumber($appID, $countryID);
        if (!($numbers[$number] ?? false)) {
            $filepath = __DIR__."/numbers/{$appID}_{$countryID}.csv";
            $fp = fopen($filepath, 'a+');
            fwrite($fp, "{$appID},{$countryID},{$number},{$requestID}\n");
            fclose($fp);

            $numbers[$number] = true;

            $fetchedNew = true;
        }

        try {
            usleep(500000);
            $this->api->rejectNumber($requestID);
        } catch (\Throwable $e) {
            throw new ReleaseNumberError("req id: {$requestID}, error msg: {$e->getMessage()}");
        }

        if (!$fetchedNew) {
            throw new AlreadyFetchedNumberException();
        }
    }

    private function readRestoredNumbers(string $appID, string $countryID): array
    {
        $filepath = __DIR__."/numbers/{$appID}_{$countryID}.csv";
        if (!file_exists($filepath)) {
            return [];
        }

        $result = [];
        $fp = fopen($filepath, 'r');
        while ($row = fgetcsv($fp)) {
            if (count($row) < 2) {
                continue;
            }

            $result["{$row[2]}"] = true;
        }
        fclose($fp);

        return $result;
    }
})->run();

