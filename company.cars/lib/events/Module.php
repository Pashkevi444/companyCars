<?php

namespace Paul\CompanyCars\Events;


use Bitrix\Main\LoaderException;
use Paul\OptionsData;
use Paul\StaticData;

final class Module
{


    /**
     * Validates the UF_IDENTIFIER field when adding a lead.
     * @param array $arFields Lead fields.
     * @return bool Returns true if the validation is successful, false otherwise.
     */
    public static function checkLeadIdentifierOnAdd(array &$arFields): bool
    {
        return self::validateLeadIdentifier($arFields);
    }

    /**
     * Validates the UF_IDENTIFIER field when updating a lead.
     * @param array $arFields Lead fields.
     * @return bool Returns true if the validation is successful, false otherwise.
     */
    public static function checkLeadIdentifierOnUpdate(array &$arFields): bool
    {
        return self::validateLeadIdentifier($arFields);
    }

    /**
     * Universal function for validating the UF_IDENTIFIER field.
     * The identifier must follow the format of 4 letters (Latin or Cyrillic) followed by a hyphen and 4 digits.
     * @param array $arFields Lead fields.
     * @return bool Returns true if the validation is successful, false otherwise.
     */
    private static function validateLeadIdentifier(array &$arFields): bool
    {
        $optionsData = OptionsData::getInstance();

        if (!isset($arFields[$optionsData->identifierUserPropertyLeadCode])){
            return true;
        }

        $identifierValue = trim($arFields[$optionsData->identifierUserPropertyLeadCode]);

        if (!$identifierValue) {
            $arFields['RESULT_MESSAGE'] = 'Значение поля '. StaticData::LEAD_IDENTIFIER_PROPERTY_NAME_RU. ' не задано';
            return false;
        }

        if (!preg_match('/^[A-Za-zА-Яа-я]{4}-\d{4}$/u', $identifierValue)) {
            $arFields['RESULT_MESSAGE'] = 'Значение поля '. StaticData::LEAD_IDENTIFIER_PROPERTY_NAME_RU. ' задано неверно';
            return false;
        }

        return true;
    }

    /**
     * @throws LoaderException
     * @throws \Exception
     */
    public static function OnBeforeProlog()
    {

        \Bitrix\Main\Loader::includeModule('crm');
        \Bitrix\Main\Loader::includeModule("paul.leadschecker");
        $propertiesOfModule = \Bitrix\Main\Config\Option::getForModule('paul.leadschecker');
        $optionsData = OptionsData::getInstance();

        try {
            $optionsData->identifierUserPropertyLeadCode = $propertiesOfModule['IDENTIFIER_USER_PROPERTY_LEAD_CODE'];

        } catch (\Exception $e) {
        }
    }
}
