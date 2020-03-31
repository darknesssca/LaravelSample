<?php


namespace App\Contracts\Repositories;


interface PolicyRepositoryContract
{
    /**
     * Выполняет поиск компании по ее символьному коду
     * Ищет только среди активных компаний
     *
     * @param $policyNumber - номер полиса или квитанции на оплату
     * @return mixed
     */
    public function getNotPaidPolicyByPaymentNumber($policyNumber);

    /**
     * Возвращает коллекцию неоплаченных полисов за последние 2 дня
     *
     * @param $limit - ограничение по количеству записей, выбираемых за раз
     * @return mixed
     */
    public function getNotPaidPolicies($limit);

    public function update($id, $data);
}
