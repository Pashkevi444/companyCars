<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/wizard.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/install/wizard_sol/utils.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/modules/company.cars/lib/StaticData.php");

class company_cars extends CModule
{
    public $MODULE_ID = 'company.cars';
    private int $testDriversGroupId;
    const string DEPARTMENTS_IBLOCK_CODE = 'departments';

    public function __construct()
    {
        if (file_exists(__DIR__ . "/version.php")) {
            $arModuleVersion = array();

            include_once(__DIR__ . "/version.php");

            $this->MODULE_ID = str_replace("_", ".", get_class($this));
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
            $this->MODULE_NAME = Loc::getMessage("COMPANY_CARS_SETTINGS_NAME");
            $this->MODULE_DESCRIPTION = Loc::getMessage("COMPANY_CARS_SETTINGS_DESCRIPTION");
            $this->PARTNER_NAME = Loc::getMessage("COMPANY_CARS_SETTINGS_PARTNER_NAME");
            $this->PARTNER_URI = Loc::getMessage("COMPANY_CARS_SETTINGS_PARTNER_URI");
        }

        return false;
    }

    /**
     * Method for installing the module.
     * It checks the Bitrix version and registers the module, then installs related events.
     * @throws Exception
     */
    public function DoInstall()
    {
        global $APPLICATION, $messages;

        try {
            if (CheckVersion(ModuleManager::getVersion("main"), "01.00.00")) {
                ModuleManager::registerModule($this->MODULE_ID);
                $this->createDriversDepartment();
                $this->createTestDrivers();
                $this->optionsSet();
            } else {
                $APPLICATION->ThrowException(
                    Loc::getMessage("COMPANY_CARS_SETTINGS_INSTALL_ERROR_VERSION")
                );
            }

            $APPLICATION->IncludeAdminFile(
                Loc::getMessage("COMPANY_CARS_SETTINGS_INSTALL_TITLE") . " \"" . Loc::getMessage("COMPANY_CARS_SETTINGS_NAME") . "\"",
                __DIR__ . "/step.php"
            );
        } catch (\Exception $exception) {
            $APPLICATION->ThrowException($exception->getMessage());
        }

        return false;
    }

    /**
     * Method for set options
     * @throws Exception
     */
    public function optionsSet(): void
    {
        $optionMapArray = [
            //'IDENTIFIER_USER_PROPERTY_LEAD_CODE' => \Paul\StaticData::LEAD_IDENTIFIER_PROPERTY_CODE,
        ];

        if (!empty($optionMapArray)) {
            foreach ($optionMapArray as $key => $value) {
                \COption::SetOptionString($this->MODULE_ID, $key, $value);
            }
        }
    }

    /**
     * Method for uninstalling the module.
     * It unregisters the module and removes related events.
     * @throws Exception
     */
    public function DoUninstall()
    {
        global $APPLICATION;

        ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->deleteDriversDepartment();
        $this->deleteTestDrivers();
        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("COMPANY_CARS_SETTINGS_UNINSTALL_TITLE") . " \"" . Loc::getMessage("COMPANY_CARS_SETTINGS_NAME") . "\"",
            __DIR__ . "/unstep.php"
        );

        return false;
    }

    private function createDriversDepartment(): void
    {
        try {
            $iblockCode = self::DEPARTMENTS_IBLOCK_CODE;
            $parentSection = \Paul\CompanyCars\StaticData::PARENT_DEPARTMENT_ID_FOR_DRIVERS ?: 1;

            $iblock = \Bitrix\Iblock\IblockTable::getList([
                'filter' => ['=CODE' => $iblockCode],
                'select' => ['ID']
            ])->fetch();

            if (!$iblock) {
                throw new \Exception("Iblock with code 'departments' not found");
            }

            $iblockId = $iblock['ID'];

            $existingSection = \Bitrix\Iblock\SectionTable::getList([
                'filter' => [
                    'IBLOCK_ID' => $iblockId,
                    '=NAME' => "Водители"
                ],
                'select' => ['ID']
            ])->fetch();

            if ($existingSection) {
                $this->testDriversGroupId = $existingSection['ID'];
            } else {

                $sectionData = [
                    'IBLOCK_ID' => $iblockId,
                    'IBLOCK_SECTION_ID' => $parentSection, // ID родительского раздела
                    'NAME' => 'Водители',
                    'CODE' => 'drivers',
                    'ACTIVE' => 'Y',
                ];

                $iblockSection = new \CIBlockSection();
                $newSectionId = $iblockSection->Add($sectionData);

                if (!$newSectionId) {
                    throw new \Exception('Ошибка при добавлении раздела: ' . $iblockSection->LAST_ERROR);
                }

                $this->testDriversGroupId = $newSectionId;
            }
        } catch (\Exception $exception) {
            throw new Exception('Ошибка при создании подразделения тестовых водителей - ' . $exception->getMessage());
        }
    }

    private function deleteDriversDepartment(): void
    {
        try {
            $iblockCode = self::DEPARTMENTS_IBLOCK_CODE;

            $iblock = \Bitrix\Iblock\IblockTable::getList([
                'filter' => ['=CODE' => $iblockCode],
                'select' => ['ID']
            ])->fetch();

            if (!$iblock) {
                throw new \Exception("Не найден инфоблок с кодом departments");
            }

            $iblockId = $iblock['ID'];

            $existingSection = \Bitrix\Iblock\SectionTable::getList([
                'filter' => [
                    'IBLOCK_ID' => $iblockId,
                    '=CODE' => 'drivers'
                ],
                'select' => ['ID']
            ])->fetch();

            if ($existingSection) {
                $sectionId = $existingSection['ID'];

                $result = \Bitrix\Iblock\SectionTable::delete($sectionId);

                if (!$result->isSuccess()) {
                    throw new \Exception(implode(", ", $result->getErrorMessages()));
                }
            }
        } catch (\Exception $exception) {
            throw new \Exception('Ошибка при удалении подразделения тестовых водителей - ' .  $exception);
        }
    }

    private function testDriversMap(): array
    {
        return [
            ["NAME" => "Иван", "LAST_NAME" => "Иванов", 'EMAIL' => 'Ivanov@example.com', 'LOGIN' => 'ivanov_driver'],
            ["NAME" => "Петр", "LAST_NAME" => "Петров", 'EMAIL' => 'Petrov@example.com', 'LOGIN' => 'Petrov_driver'],
        ];
    }

    private function createTestDrivers(): void
    {
        try {
            if (empty($this->testDriversGroupId)) {
                throw new \Exception('Не найдено подразделение водителей');
            }

            $drivers = $this->testDriversMap();

            foreach ($drivers as $driver) {
                $user = new \CUser;
                $userFields = [
                    "NAME" => $driver["NAME"],
                    "LAST_NAME" => $driver["LAST_NAME"],
                    "EMAIL" => $driver['EMAIL'],
                    "LOGIN" => $driver['LOGIN'],
                    "PASSWORD" => "12345678",
                    "UF_DEPARTMENT" => [$this->testDriversGroupId],
                ];
                $user->Add($userFields);
            }
        } catch (\Exception $exception) {
            throw new Exception('Ошибка при создании тестовых водителей - ' . $exception->getMessage());
        }
    }
    private function deleteTestDrivers(): void
    {
        try {
            $drivers = $this->testDriversMap();

            foreach ($drivers as $driver) {
                $filter = [
                    "LOGIN" => $driver['LOGIN'],
                    "EMAIL" => $driver['EMAIL'],
                ];

                $dbUsers = \CUser::GetList(($by = "id"), ($order = "asc"), $filter);

                while ($user = $dbUsers->Fetch()) {
                    $userId = $user['ID'];
                    if (!\CUser::Delete($userId)) {
                        throw new \Exception("Не удалось удалить пользователя с ID {$userId}");
                    }
                }
            }
        } catch (\Exception $exception) {
            throw new \Exception('Ошибка при удалении тестовых водителей - ' . $exception->getMessage());
        }
    }

}
