<?php


namespace App\Contracts\Repositories;


interface PolicyRepositoryContract extends AbstractRepositoryInterface
{
    /**
     * Выполняет поиск компании по ее символьному коду
     * Ищет только среди активных компаний
     *
     * @param $policyNumber - номер полиса или квитанции на оплату
     * @return mixed
     */
    public function getNotPaidPolicyByPaymentNumber($policyNumber);
}
