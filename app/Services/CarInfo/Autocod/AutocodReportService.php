<?php


namespace App\Services\CarInfo\Autocod;


use Benfin\Api\GlobalStorage;

class AutocodReportService extends AutocodService
{
    private $token;

    public function __construct()
    {
        parent::__construct();
        $this->token = $this->createToken();
    }

    /**запросить генерацию отчета для автозаполнения
     * @param string $vin
     * @param string $uid
     * @return array
     * @throws \Exception
     */
    public function getReport(string $vin, string $uid): array
    {
        $data = [
            "query_type" => "VIN",
            "query" => $vin,
        ];
        $headers = [
            'Authorization' => 'AR-REST ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $res = $this->postRequest($this->baseurl . 'user/reports/' . $uid . '/_make', $data, $headers,false,false,true);
        if(!empty($res['status']) && $res['status'] === 400)
            throw new \Exception("Некорректный запрос");
        if($res['state'] !== 'ok') {
            if ($res['event']['type'] == 'ValidationFailed') {
                throw new \Exception('Некорректный формат VIN-номера');
            }
            throw new \Exception($res['event']['message']);
        }
        $this->logger->sendLog("Запрошен отчет autocod: vin=$vin, uid=$uid", env("LOG_MICROSERVICE_CODE"));
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
     * @param string $vin
     * @return array
     * @throws \Exception
     */
    public function readReportAutocompleteSync(string $vin): array
    {
        $wait = 9999;
        $result = $this->getReport($vin, $this->uid_autocomplete);
        while ($wait > 0) { //если все операции завершены, то выводим отчет
            $r2 = $this->readReport($result['report_id']); //запрашиваем отчет
            $wait = intval($r2['data'][0]['progress_wait']); //количество ожидающих операций
            sleep(0.2);
        }
        if(empty($r2['data'][0]['content']))
            throw new \Exception("По заданному VIN ничего не найдено");
        return $r2;
    }

    /**проверка лицензии такси
     * @param $vin
     * @return bool
     * истина, если есть записи такси
     * @throws \Exception
     */
    public function checkTaxi($vin)
    {
        $result = $this->getReport($vin, $this->uid_taxi); //запрашиваем отчет
        $r2 = $this->readReport($result['report_id']);
        $cnt = intval($r2['data'][0]['content']['taxi']['history']['count']);
        if ($cnt > 0) {
            foreach ($r2['data'][0]['content']['taxi']['history']['items'] as $item) {
                if ($item['license']['status'] == "ACTIVE") {
                    return true;
                }
            }
        }
        return false;
    }
}
