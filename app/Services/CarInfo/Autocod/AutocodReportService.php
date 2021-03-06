<?php


namespace App\Services\CarInfo\Autocod;


use App\Traits\CacheStore;
use Benfin\Api\GlobalStorage;
use Benfin\Log\Facades\Log;

class AutocodReportService extends AutocodService
{
    use CacheStore;

    private $token;

    public function __construct()
    {
        parent::__construct();
        $this->token = $this->createToken();
    }

    /**запросить генерацию отчета для автозаполнения
     * @param string $value
     * @param string $queryType
     * @param string $uid
     * @return array
     * @throws \Exception
     */
    public function getReport(string $value, string $queryType, string $uid): array
    {
        $data = [
            "queryType" => $queryType,
            "query" => $value,
        ];
        $headers = [
            'Authorization' => 'AR-REST ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $res = $this->postRequest($this->baseurl . 'user/reports/' . $uid . '/_make', $data, $headers, false, false, true);
        if (!empty($res['status']) && $res['status'] === 400)
            throw new \Exception("Некорректный запрос");
        if (!isset($res['state'])) {
            throw new \Exception('При получени данных из автокода произошла ошибка. Попробуйте еще раз.');
        }
        if ($res['state'] !== 'ok') {
            if ($res['event']['type'] === 'ValidationFailed') {
                if ($queryType === 'GRZ') {
                    throw new \Exception("Некорректный формат госномера");
                }
                throw new \Exception("Некорректный формат $queryType номера");
            }
            throw new \Exception($res['event']['message']);
        }
        $this->logger->sendLog("Запрошен отчет autocod: value=$value, queryType=$queryType, uid=$uid", env("LOG_MICROSERVICE_CODE"));
        return ['report_id' => $res['data'][0]['uid'], 'suggest_get' => $res['data'][0]['suggest_get']];
    }

    /**получить уже готовый отчет автозаполнения
     * @param $report_id
     * uid сгенерированного отчета
     * @return array
     * @throws \Exception
     */
    public function readReport($report_id): array
    {
        $data = [
            '_content' => true,
            '_detailed' => true,
        ];
        $headers = [
            'Authorization' => 'AR-REST ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $request = $this->getRequest($this->baseurl . 'user/reports/' . $report_id, $data, $headers, false);
        return $request;
    }

    /**запросить генерацию отчета и вернуть готовый отчет
     * @param string $value
     * @param string $queryType
     * @param bool $eosago
     * @param bool $unauthorized
     * @return array
     * @throws \Exception
     */
    public function readReportAutocompleteSync(string $value, string $queryType, bool $eosago = false, $unauthorized = false): array
    {
        $wait = 9999;
        $result = $this->getReport($value, $queryType, $this->uid_autocomplete);
        while ($wait > 0) { //если все операции завершены, то выводим отчет
            $r2 = $this->readReport($result['report_id']); //запрашиваем отчет
            $wait = (int)empty($r2['data'][0]) || (int)$r2['data'][0]['progress_wait']; //количество ожидающих операций
            sleep(0.2);
        }
        Log::daily(
            $r2,
            'AutocodReports',
            "{$queryType}|{$value}|Autocomplete"
        );
        if (empty($r2['data'][0]['content'])) {
            if (!$unauthorized) {
                $this->put(
                    $this->getId('autocod', GlobalStorage::getUserId(), $queryType, $value, 'isExist'),
                    ['status' => false]
                );
            }
            if ($eosago) {
                $r2['found'] = false;
                return $r2;
            }
            $exceptionMessage = $queryType === 'VIN' ? "По заданному VIN ничего не найдено" : "По заданному госномеру ничего не найдено. Заполните данные о ТС вручную";
            throw new \Exception($exceptionMessage);
        }
        if (!$unauthorized) {
            $this->put(
                $this->getId('autocod', GlobalStorage::getUserId(), $queryType, $value, 'isExist'),
                ['status' => true]
            );
        }
        $r2['found'] = true;
        return $r2;
    }

    /**проверка лицензии такси
     * @param $value
     * @param $queryType
     * @param bool $eosago
     * @param bool $unauthorized
     * @return bool
     * истина, если есть записи такси
     * @throws \Exception
     */
    public function checkTaxi($value, $queryType, $eosago = false, $unauthorized = false)
    {
        $result = $this->getReport($value, $queryType, $this->uid_taxi); //запрашиваем отчет
        $wait = 9999;
        while ($wait > 0) { //если все операции завершены, то выводим отчет
            $r2 = $this->readReport($result['report_id']);
            $wait = (int)$r2['data'][0]['progress_wait']; //количество ожидающих операций
            sleep(0.2);
        }
        Log::daily(
            $r2,
            'AutocodReports',
            "{$queryType}|{$value}|checkTaxi"
        );
        if (empty($r2['data'][0]['content'])) {
            throw new \Exception('Автокод не предоставил данные по ТС. Попробуйте еще раз.');
        }
        if (
            !isset($r2['data'][0]['content']['taxi']['history']['count']) ||
            !isset($r2['data'][0]['content']['taxi']['history']['items'])
        ) {
            throw new \Exception('При получени данных из автокода произошла ошибка. Попробуйте еще раз.');
        }
        $cnt = (int)$r2['data'][0]['content']['taxi']['history']['count'];
        if ($cnt > 0) {
            foreach ($r2['data'][0]['content']['taxi']['history']['items'] as $item) {
                if ($item['license']['status'] === "ACTIVE") {
                    if (!$unauthorized) {
                        $this->put(
                            $this->getId('autocod', GlobalStorage::getUserId(), $queryType, $value, 'isTaxi'),
                            ['status' => true]
                        );
                    }
                    return true;
                }
            }
        }
        if (!$unauthorized) {
            $this->put(
                $this->getId('autocod', GlobalStorage::getUserId(), $queryType, $value, 'isTaxi'),
                ['status' => false]
            );
        }
        return false;
    }
}
