<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Hsldymq\SMS\SMSMan\Country;
use Hsldymq\SMS\SMSMan\Application;
use Hsldymq\SMS\SMSMan\SMSManAPI;
use Hsldymq\SMS\SMSMan\Exception\ReleaseNumberError;
use Hsldymq\SMS\SMSMan\Exception\APIError;
use Hsldymq\SMS\SMSMan\Exception\AlreadyFetchedNumberException;
use Hsldymq\SMS\Utils\PhoneNoParser;

(new class{
    private string $token = 'w3FbfzHdttzCcpCV44SbWBtvUvNQSFjS';

    private Country $country;
    private Application $application;
    private SMSManAPI $api;

    private string $countryID;
    private string $countryCode;

    private int $rejectErrCount = 0;
    private int $rejectErrorLimit = 10;

    private int $noNumberCount = 0;

    private array $skipAppCountry = [
        '14' => ['220', '181'],
        '140' => ['576', '140', '2323'],
    ];

    public function __construct()
    {
        global $argv;
        if (count($argv) < 2) {
            throw new Exception('provide country id');
        }

        ini_set('memory_limit', '4G');
        $this->api = new SMSManAPI($this->token);
        $this->country = new Country($this->token);
        $this->application = new Application($this->token);
        $this->countryID = strval($argv[1]);
        $this->countryCode = $this->country->getCodeByID($this->countryID, true);
    }

    public function run()
    {
        $count = 0;
        $countryID = $this->countryID;
        $numbers = $this->api->getNumberLimitsByCountryID($countryID, 'desc');
        foreach ($numbers as $each) {
            $this->noNumberCount = 0;
            $appID = $each['appID'];
            if (in_array($appID, $this->skipAppCountry[$countryID] ?? [])) {
                continue;
            }

            tryAgain:
            $remainNum = $this->api->getNumberLimit($appID, $countryID);
            $numbers = $this->readRestoredNumbers($appID, $countryID);

            ensureAll:
            while (count($numbers) < $remainNum * 0.8) {
                $countryCode = $this->api->getCodeByID($countryID, false);
                echo "$count: $countryID($countryCode) - $appID ... ";
                try {
                    $this->fetchAndRelease($appID, $countryID, $numbers);
                } catch (APIError $e) {
                    echo "{$e->getMessage()} (".count($numbers)."/{$remainNum})".PHP_EOL;
                    if ($e->getMessage() === 'no_numbers') {
                        if (++$this->noNumberCount >= 50) {
                            continue 2;
                        }
                    }
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

            $remainNum = $this->api->getNumberLimit($appID, $countryID);
            if (count($numbers) < $remainNum * 0.8) {
                goto ensureAll;
            }
        }
    }

    private function fetchAndRelease(string $appID, string $countryID, array &$numbers): void
    {
        $fetchedNew = false;

        list($phoneNo, $requestID) = $this->api->getNumber($appID, $countryID);
        if (!($numbers[$phoneNo] ?? false)) {
            [$callingCode, $phoneNo] = PhoneNoParser::parsePhone($phoneNo, $this->countryCode);
            $filepath = __DIR__."/numbers/{$countryID}_{$appID}.csv";
            $fp = fopen($filepath, 'a+');
            fputcsv($fp, [
                $callingCode,
                $phoneNo,
                $this->countryCode,
                $appID,
                $countryID,
                $requestID,
            ]);
            fclose($fp);

            $numbers["{$callingCode}{$phoneNo}"] = true;
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
        $filepath = __DIR__."/numbers/{$countryID}_{$appID}.csv";
        if (!file_exists($filepath)) {
            return [];
        }

        $result = [];
        $fp = fopen($filepath, 'r');
        while ($row = fgetcsv($fp)) {
            if (count($row) < 2) {
                continue;
            }

            $result["{$row[0]}{$row[1]}"] = true;
        }
        fclose($fp);

        return $result;
    }
})->run();

