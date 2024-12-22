<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if(!check_bitrix_sessid()){

    return;
}

if($errorException = $APPLICATION->GetException()){

    echo(CAdminMessage::ShowMessage($errorException->GetString()));
}else{
    global $messages;

    echo(CAdminMessage::ShowNote(Loc::getMessage("COMPANY_CARS_SETTINGS_STEP_BEFORE")." ".Loc::getMessage("COMPANY_CARS_SETTINGS_STEP_AFTER")));
    echo implode('<br>', $messages ?? []);
}
?>

<form action="<? echo($APPLICATION->GetCurPage()); ?>">
    <input type="hidden" name="lang" value="<? echo(LANG); ?>" />
    <input type="submit" value="<? echo(Loc::getMessage("COMPANY_CARS_SETTINGS_STEP_SUBMIT_BACK")); ?>">
</form>