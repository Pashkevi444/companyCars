<?php
\Bitrix\Main\Loader::registerAutoLoadClasses(
    "company.cars",
    array(
        'Paul\\CompanyCars\\Events\\Module' => 'lib/events/Module.php',
        'Paul\\CompanyCars\\OptionsData' => 'lib/OptionsData.php',
        'Paul\\CompanyCars\\StaticData' => 'lib/StaticData.php',
    )
);
