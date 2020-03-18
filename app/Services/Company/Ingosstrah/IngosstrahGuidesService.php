<?php


namespace App\Services\Company\Ingosstrah;


use App\Contracts\Company\Ingosstrah\IngosstrahLoginServiceContract;
use App\Http\Controllers\SoapController;
use App\Services\Company\GuidesSourceInterface;
use App\Services\Company\Soglasie\SoglasieService;

class IngosstrahGuidesService extends IngosstrahService implements GuidesSourceInterface
{
    private $baseUrl;

    public function __construct()
    {
        parent::__construct();
    }


    public function updateGuides(): bool
    {
        $token = $this->getToken();
        $data = [
            'SessionToken' => $token,
                        "Product" => '753518300', //todo из справочника, вероятно статика
        ];
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'GetDicti', [], [], [], []);
        dd($response);
    }

    /**получение токена
     * @return string
     */
    private function getToken(): string
    {
        $serviceLogin = app(IngosstrahLoginServiceContract::class);
        $loginData = $serviceLogin->run(null, null);
        return $loginData['sessionToken'];
    }
}
