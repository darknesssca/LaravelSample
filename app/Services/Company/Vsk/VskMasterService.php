<?php


namespace App\Services\Company\Vsk;


use App\Contracts\Company\Vsk\VskMasterServiceContract;
use App\Exceptions\MethodForbiddenException;
use App\Models\InsuranceCompany;

class VskMasterService extends VskService implements VskMasterServiceContract
{

    /**
     * Метод рассчитывает премию за страховку, либо отправляет запрос в шину СК (в зависимости от того, как работает СК)
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     */
    public function calculate(InsuranceCompany $company, $attributes): array
    {
        // TODO: Implement calculate() method.
    }

    /**
     * Метод создает полис в СК либо отправляет создание полиса в шину СК (в зависимости от того, как работает СК)
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     */
    public function create(InsuranceCompany $company, $attributes): array
    {
        // TODO: Implement create() method.
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
     * @throws MethodForbiddenException - выбрасывается в случаях, когда метод не требуется для данного СК и не был
     * реализован, но его все равно пытаются вызвать
     */
    public function preCalculating(InsuranceCompany $company, $attributes): void
    {
        // TODO: Implement preCalculating() method.
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
     * @throws MethodForbiddenException - выбрасывается в случаях, когда метод не требуется для данного СК и не был
     * реализован, но его все равно пытаются вызвать
     */
    public function segmentCalculating(InsuranceCompany $company, $attributes): void
    {
        // TODO: Implement segmentCalculating() method.
    }

    /**
     * Метод выполняет запрос в шину СК для получения данных создания заявки
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return void
     * @throws MethodForbiddenException - выбрасывается в случаях, когда метод не требуется для данного СК и не был
     * реализован, но его все равно пытаются вызвать
     */
    public function creating(InsuranceCompany $company, $attributes): void
    {
        // TODO: Implement creating() method.
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
     * @throws MethodForbiddenException - выбрасывается в случаях, когда метод не требуется для данного СК и не был
     * реализован, но его все равно пытаются вызвать
     */
    public function calculating(InsuranceCompany $company, $attributes): array
    {
        // TODO: Implement calculating() method.
    }

    /**
     * Метод возвращает текущий статус заявки, отправленной на создание (для СК, где запросы идут через шину)
     *
     * @param InsuranceCompany $company - объект выбранной компании
     * @param $attributes - массив атрибутов, прошедших валидацию
     * @return array
     * @throws MethodForbiddenException - выбрасывается в случаях, когда метод не требуется для данного СК и не был
     * реализован, но его все равно пытаются вызвать
     */
    public function processing(InsuranceCompany $company, $attributes): array
    {
        // TODO: Implement processing() method.
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
