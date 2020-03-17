<?php


namespace App\Services\CarInfo\Autocode;


class AutocodeReportService extends AutocodeService
{
    private $token;

    public function __construct()
    {
        parent::__construct();
        $this->token = $this->createToken();
    }

    //отчет не готов
    //"progress_ok": 0,
    //"progress_wait": 2,
    //"progress_error": 0,

    //завершившийся отчет
    //"progress_ok": 2,
    //"progress_wait": 0,
    //"progress_error": 1,

    /**запросить генерацию отчета для автозаполнения
     * @param $vin
     * @return array
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
        $res = $this->sendPost($this->baseurl . 'user/reports/' . $uid . '/_make', $data, $headers);
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
    public function checkTaxi($vin): bool
    {
        $result = $this->getReport($vin, $this->uid_taxi); //запрашиваем отчет
        $r2 = $this->readReport($result['report_id']);
        $cnt = count($r2['content']['taxi']['history']['count']);
        return $cnt > 0;
    }
}
