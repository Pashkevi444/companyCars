<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if(!check_bitrix_sessid()){

    return;
}
global $messages;

echo(CAdminMessage::ShowNote(Loc::getMessage("COMPANY_CARS_SETTINGS_UNSTEP_BEFORE")." ".Loc::getMessage("COMPANY_CARS_SETTINGS_UNSTEP_AFTER")));


echo implode('<br>', $messages ?? []);

?>

<form action="<? echo($APPLICATION->GetCurPage()); ?>">
    <input type="hidden" name="lang" value="<? echo(LANG); ?>" />
    <input type="submit" value="<? echo(Loc::getMessage("COMPANY_CARS_SETTINGS_UNSTEP_SUBMIT_BACK")); ?>">
</form>