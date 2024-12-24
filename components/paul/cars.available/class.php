<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Engine\Contract\Controllerable;

class FreeCarsApiComponent extends CBitrixComponent implements Controllerable, \Bitrix\Main\Errorable
{
    /** @var ErrorCollection */
    protected $errorCollection;
    const int HB_ID = 4;

    public function onPrepareComponentParams($arParams)
    {

        $this->errorCollection = new ErrorCollection();
        return parent::onPrepareComponentParams($arParams);
    }

    public function configureActions()
    {
        return [
            'getAvailableCars' => [
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\Authentication(),
                    new \Bitrix\Main\Engine\ActionFilter\HttpMethod([
                        \Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_GET
                    ]),
                    new \Bitrix\Main\Engine\ActionFilter\Csrf(),
                ]
            ],
        ];
    }

    public function executeComponent()
    {
        $this->includeComponentTemplate(); // Include template for rendering
    }

    /**
     * Action to get available cars.
     *
     * @param string $startTime Start time of the interval.
     * @param string $endTime End time of the interval.
     * @return array|null
     */
    public function getAvailableCarsAction(string $startTime, string $endTime): ?array
    {

        if (empty($optionsData->ridesHbId)){
            $this->errorCollection[] = new Error('Missing highloadBlockId check module settings');
            return null;
        }

        // Get the current user ID
        $userId = \Bitrix\Main\Engine\CurrentUser::get()->getId();

        if (!$userId) {
            $this->errorCollection[] = new Error('Failed to get current user ID');
            return null;
        }

        // Validate parameters
        if (!$startTime || !$endTime) {
            $this->errorCollection[] = new Error('Missing parameters: startTime or endTime');
            return null;
        }

        try {
            return $this->getAvailableCars($userId, $startTime, $endTime);
        } catch (\Exception $e) {
            $this->errorCollection[] = new Error('Error processing data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get available cars based on comfort and availability.
     *
     * Filters cars by comfort categories and availability in the given time interval.
     *
     * @param int $userId User ID.
     * @param string $startTime Start time of the interval.
     * @param string $endTime End time of the interval.
     * @return array List of available cars.
     */
    private function getAvailableCars(int $userId, string $startTime, string $endTime): array
    {
        $query = \Bitrix\Iblock\Elements\ElementCarsTable::query();

        // Link comfort category
        $query->registerRuntimeField(
            'COMFORTS',
            new \Bitrix\Main\Entity\ReferenceField(
                'COMFORTS',
                \Bitrix\Iblock\Elements\ElementComfortsTable::class,
                ['this.COMFORT_CATEGORY.VALUE' => 'ref.ID'],
                ['join_type' => 'left']
            )
        );

        // Link driver information
        $query->registerRuntimeField(
            'DRIVERS',
            new \Bitrix\Main\Entity\ReferenceField(
                'DRIVERS',
                \Bitrix\Iblock\Elements\ElementDriversTable::class,
                ['this.DRIVER.VALUE' => 'ref.ID'],
                ['join_type' => 'left']
            )
        );

        // Filter cars by available comfort categories
        $comfortIds = $this->getAvailableComforts($userId);
        $query->whereIn('COMFORTS.ID', $comfortIds);

        // Exclude occupied cars
        $takenCarIds = $this->getTakenCars($startTime, $endTime);
        $query->whereNotIn('ID', $takenCarIds);

        $query->setSelect([
            'CAR_MODEL' => 'NAME',
            'COMFORT_NAME' => 'COMFORTS.NAME',
            'DRIVER_NAME' => 'DRIVERS.NAME',
        ]);

        return $query->fetchAll() ?? [];
    }

    /**
     * Get available comfort categories for the user.
     *
     * Retrieves IDs of comfort categories based on the user's position.
     *
     * @param int $userId User ID.
     * @return array List of available comfort categories.
     */
    private function getAvailableComforts(int $userId): array
    {
        $query = \Bitrix\Main\UserTable::query()
            ->setFilter(['ID' => $userId])
            ->registerRuntimeField(
                'POSITION_COMFORTS',
                new \Bitrix\Main\Entity\ReferenceField(
                    'POSITION_COMFORTS',
                    \Bitrix\Iblock\Elements\ElementPositionsTable::class,
                    ['this.UF_POSITION' => 'ref.ID'],
                    ['join_type' => 'left']
                )
            )
            ->setSelect(['POSITION_COMFORTS.ALLOWED_COMFORTS.IBLOCK_GENERIC_VALUE']);
        $result = $query->fetchAll();

        $allowedComfortIds = array_column($result, "MAIN_USER_POSITION_COMFORTS_ALLOWED_COMFORTS_IBLOCK_GENERIC_VALUE");

        return $allowedComfortIds;
    }

    /**
     * Get list of occupied cars during the given time interval.
     *
     * Checks the highload block to find cars occupied in the specified period.
     *
     * @param string $startTime Start time of the interval.
     * @param string $endTime End time of the interval.
     * @return array List of occupied car IDs.
     */
    private function getTakenCars(string $startTime, string $endTime): array
    {
        $startTimeBitrix = new \Bitrix\Main\Type\DateTime($startTime, 'Y-m-d H:i');
        $endTimeBitrix = new \Bitrix\Main\Type\DateTime($endTime, 'Y-m-d H:i');

        $ridesHlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById(self::HB_ID)->fetch();
        $ridesEntity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($ridesHlblock)->getDataClass();

        // Find cars occupied in the given time interval
        $filter = \Bitrix\Main\Entity\Query::filter()
            ->logic('or')
            ->whereBetween('UF_START_TIME', $startTimeBitrix, $endTimeBitrix)
            ->whereBetween('UF_END_TIME', $startTimeBitrix, $endTimeBitrix);

        $query = (new \Bitrix\Main\Entity\Query($ridesEntity::getEntity()))
            ->setSelect(['UF_CAR'])
            ->where($filter);

        $result = $query->fetchAll();

        return array_column($result, 'UF_CAR');
    }

    public function getErrors()
    {
        return $this->errorCollection->toArray(); // Return all collected errors
    }

    public function getErrorByCode($code)
    {
        return $this->errorCollection->getErrorByCode($code); // Return specific error by code
    }
}
