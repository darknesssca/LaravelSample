<?php


namespace App\Services\Company\Vsk;


use App\Contracts\Company\Vsk\VskBuyPolicyServiceContract;
use App\Contracts\Company\Vsk\VskCalculatePolicyServiceContract;
use App\Contracts\Company\Vsk\VskLoginServiceContract;
use App\Contracts\Company\Vsk\VskMasterServiceContract;
use App\Contracts\Company\Vsk\VskSavePolicyServiceContract;
use App\Contracts\Company\Vsk\VskSignPolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\MethodForbiddenException;
use App\Exceptions\TokenException;
use App\Models\InsuranceCompany;
use Benfin\Api\GlobalStorage;

class VskMasterService extends VskService implements VskMasterServiceContract
{

    /**
     * Чтобы не дописывать логику на фронте поменял ее здесь:
     * метод calculate производит авторизацию пользователя
     * метод preCalculating производит расчет премии
     * все остальные методы работают согласно названиям
     */

    /**
     * Метод рассчитывает премию за страховку, либо отправляет запрос в шину СК (в зависимости от того, как работает СК)
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     * @throws TokenException
     */
    public function calculate(InsuranceCompany $company, $attributes): array
    {
        $this->pushForm($attributes);

        /** @var VskLoginServiceContract $loginService */
        $loginService = app(VskLoginServiceContract::class);
        $loginData = $loginService->run($company, $attributes);

        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code] = [
            'status' => 'calculating',
            'stage' => 'login',
            'loginUniqueId' => $loginData['uniqueId'],
            'user' => GlobalStorage::getUser(),
        ];
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);

        return [
            'status' => 'calculating',
        ];
    }

    /**
     * Метод создает полис в СК либо отправляет создание полиса в шину СК (в зависимости от того, как работает СК)
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     * @throws TokenException
     */
    public function create(InsuranceCompany $company, $attributes): array
    {
        $this->pushForm($attributes);

        /** @var VskSavePolicyServiceContract $savePolicyService */
        $savePolicyService = app(VskSavePolicyServiceContract::class);
        $savePolicyData = $savePolicyService->run($company, $attributes);

        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code]['status'] = 'processing';
        $tokenData[$company->code]['stage'] = 'create';
        $tokenData[$company->code]['savePolicyUniqueId'] = $savePolicyData['uniqueId'];
        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);

        return [
            'status' => 'processing',
        ];
    }

    /**
     * Метод отмечает полис как оплаченный по входящему запросу со стороны СК
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return void
     * @throws MethodForbiddenException - выбрасывается в случаях, когда метод не требуется для данного СК и не был
     * реализован, но его все равно пытаются вызвать
     */
    public function payment(InsuranceCompany $company, $attributes): void
    {
        // TODO: Implement payment() method.
    }

    /**
     * Метод выполняет запрос в шину СК для получения данных предварительного рассчета
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return void
     * @throws TokenException
     */
    public function preCalculating(InsuranceCompany $company, $attributes): void
    {
        $this->pushForm($attributes);

        /** @var VskCalculatePolicyServiceContract $calcService */
        $calcService = app(VskCalculatePolicyServiceContract::class);
        $calcData = $calcService->run($company, $attributes);

        $tokenData = $this->getTokenData($attributes['token'], true);
        $tokenData[$company->code]['stage'] = 'calculate';
        $tokenData[$company->code]['calculateUniqueId'] = $calcData['uniqueId'];

        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
    }

    /**
     * Метод выполняет запрос в шину СК для получения данных сегментации
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return void
     * @throws MethodForbiddenException - выбрасывается в случаях, когда метод не требуется для данного СК и не был
     * реализован, но его все равно пытаются вызвать
     */
    public function segmenting(InsuranceCompany $company, $attributes): void
    {
        // TODO: Implement segmenting() method.
    }

    /**
     * Метод выполняет запрос в шину СК для получения данных окончательного рассчета
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return void
     * @throws TokenException
     */
    public function segmentCalculating(InsuranceCompany $company, $attributes): void
    {
    }

    /**
     * Метод выполняет запрос в шину СК для получения данных создания заявки
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return void
     * @throws TokenException
     */
    public function creating(InsuranceCompany $company, $attributes): void
    {
        if (!empty($attributes['method'])){
            $this->pushForm($attributes);

            /** @var VskBuyPolicyServiceContract $buyPolicyService */
            $buyPolicyService = app(VskBuyPolicyServiceContract::class);
            $buyPolicyData = $buyPolicyService->run($company, $attributes);

            $tokenData = $this->getTokenData($attributes['token'], true);
            $tokenData[$company->code]['stage'] = 'buy';
            $tokenData[$company->code]['buyUniqueId'] = $buyPolicyData['uniqueId'];
        } else {
            /** @var VskSignPolicyServiceContract $signPolicyService */
            $signPolicyService = app(VskSignPolicyServiceContract::class);
            $signPolicyData = $signPolicyService->run($company, $attributes);

            $tokenData = $this->getTokenData($attributes['token'], true);
            $tokenData[$company->code]['status'] = 'processing';
            $tokenData[$company->code]['stage'] = 'sign';
            $tokenData[$company->code]['signUniqueId'] = $signPolicyData['uniqueId'];
        }

        $this->intermediateDataService->update($attributes['token'], [
            'data' => json_encode($tokenData),
        ]);
    }

    /**
     * Метод выполняет запрос в шину СК для получения данных созданой но не получившей статуса готовой к оплате заявки
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return void
     * @throws MethodForbiddenException - выбрасывается в случаях, когда метод не требуется для данного СК и не был
     * реализован, но его все равно пытаются вызвать
     */
    public function holding(InsuranceCompany $company, $attributes): void
    {
        // TODO: Implement holding() method.
    }

    /**
     * Метод возвращает текущий статус заявки, отправленной на рассчет (для СК, где запросы идут через шину)
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     * @throws TokenException
     * @throws ApiRequestsException
     */
    public function calculating(InsuranceCompany $company, $attributes): array
    {
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);

        if (!(isset($tokenData['status']) && $tokenData['status'])) {
            throw new TokenException('Нет данных о статусе рассчета в токене');
        }

        switch ($tokenData['status']) {
            case 'calculating':
                return [
                    'status' => 'calculating',
                ];
            case 'calculated':
                return [
                    'status' => 'done',
                    'premium' => $tokenData['premium'],
                    'reward' => $tokenData['reward']
                ];
            case 'error':
                throw new ApiRequestsException($tokenData['errorMessages']);
            default:
                throw new TokenException('Статус рассчета не валиден');
        }
    }

    /**
     * Метод возвращает текущий статус заявки, отправленной на создание (для СК, где запросы идут через шину)
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     * @throws ApiRequestsException
     * @throws TokenException
     */
    public function processing(InsuranceCompany $company, $attributes): array
    {
        $tokenData = $this->getTokenDataByCompany($attributes['token'], $company->code);
        if (!(isset($tokenData['status']) && $tokenData['status'])) {
            throw new TokenException('Нет данных о статусе рассчета в токене');
        }

        switch ($tokenData['status']) {
            case 'processing':
                return [
                    'status' => 'processing',
                ];
            case 'signing':
                return [
                    'status' => 'done',
                    'sign' => true
                ];
            case 'buying':
                return [
                    'status' => 'done',
                ];
            case 'error':
                throw new ApiRequestsException($tokenData['errorMessages']);
            default:
                throw new TokenException('Статус рассчета не валиден');
        }
    }

    /**
     * Метод выполняет проверку статусов оплаты через методы СК
     *
     * @param InsuranceCompany $company
     * @param $attributes
     * @return void
     * @throws MethodForbiddenException - выбрасывается в случаях, когда метод не требуется для данного СК и не был
     * реализован, но его все равно пытаются вызвать
     */
    public function getPayment(InsuranceCompany $company, $attributes): void
    {
        // TODO: Implement getPayment() method.
    }
}
