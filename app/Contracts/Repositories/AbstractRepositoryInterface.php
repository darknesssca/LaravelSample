<?php


namespace App\Contracts\Repositories;


use Illuminate\Database\Eloquent\Model;

interface AbstractRepositoryInterface
{
    /**
     * Сохраняет обект в локальном хранилище для обеспечения доступа к нему в пределах всего кода
     *
     * @param $id - идентификатор записи
     * @param Model $model - сохраняемый объект
     * @return mixed
     */
    public function save($id, Model $model);

    /**
     * Возвращает объект из локального хранилища, либо запрашивает новый, если в хранилище такого объекта нет
     *
     * @param $id - идентификатор записи
     * @return mixed
     */
    public function load($id);

    /**
     * Проверяет есть ли запись с таким id в хранилище
     *
     * @param $id - идентификатор записи
     * @return mixed
     */
    public function isStored($id);

    /**
     * Удаляет запись из локального хранилища
     *
     * @param $id - идентификатор записи
     * @return mixed
     */
    public function drop($id);

    /**
     * Возвращает запись из БД по идентификатолру
     * поддерживает любой тип и имя primaryKey
     *
     * @param $id - идентификатор записи
     * @return mixed
     */
    public function find($id);

    /**
     * Возвращает запись из БД по id
     * Поддерживает любой тип primaryKey, но требует, чтобы primaryKey имел имя id
     *
     * @param $id - идентификатор записи
     * @return mixed
     */
    public function getById($id);

    /**
     * Создает запись в БД
     *
     * @param array $data - массив данных объекта
     * @return mixed
     */
    public function create(array $data);

    /**
     * Обновляет запись в БД
     *
     * @param $id - идентификатор записи
     * @param array $data - массив данных объекта
     * @return mixed
     */
    public function update($id, array $data);

    /**
     * Удаляет запись из БД
     *
     * @param $id - идентификатор записи
     * @return mixed
     */
    public function delete($id);
}
