<?php


namespace App\Services\CarInfo\Autocod;


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
     */
    public function getReport(string $vin, string $uid): array
    {
        if (env('APP_DEBUG') && $uid == $this->uid_autocomplete) {
            return ['report_id' => 'benfin_autocomplete_plus_report_Z94CB41AAGR322020@benfin', 'suggest_get' => '0'];
        }
        if (env('APP_DEBUG') && $uid == $this->uid_taxi) {
            return ['report_id' => 'benfin_active_taxi_license_report_Z94CB41AAGR422720@benfin', 'suggest_get' => '0'];
        }

        $data = [
            "query_type" => "VIN",
            "query" => $vin,
        ];
        $headers = [
            'Authorization' => 'AR-REST ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $res = $this->sendPost($this->baseurl . 'user/reports/' . $uid . '/_make', $data, $headers);
        $this->sendLog("Запрошен отчет autocod: vin=$vin, uid=$uid", env("LOG_MICROSERVICE_CODE"));
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
        $res = $this->sendGet($this->baseurl . 'user/reports/' . $report_id, $data, $headers);
        return $res;
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
        return $cnt > 0;
    }
}
