<?php
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;

if (!$USER->IsAdmin()) {
    return;
}

Loc::loadMessages(__FILE__);

final class ModuleSettings
{
    // Module identifier
    private string $moduleId;

    // List of tabs for settings page
    private array $tabs = [];

    /**
     * Constructor to initialize the module settings.
     *
     * @param string $moduleId The ID of the module.
     */
    public function __construct(string $moduleId)
    {
        $this->moduleId = htmlspecialcharsbx($moduleId);

        $this->initializeModules();
        $this->initializeTabs();
    }

    /**
     * Initializes the required modules for the functionality.
     *
     * Loads the current module and other required modules.
     */
    private function initializeModules(): void
    {
        Loader::includeModule($this->moduleId);
    }

    /**
     * Initializes the tabs for the settings form.
     *
     * Populates the `$tabs` property with tab configurations.
     */
    private function initializeTabs(): void
    {
        $arAllOptions = $this->getAllOptions();

        $this->tabs = [
            [
                'DIV' => 'paul_main',
                'TAB' => 'Main',
                'TITLE' => 'Main options',
                'OPTIONS' => $arAllOptions['general']
            ],
        ];
    }

    /**
     * Retrieves all available options for the settings.
     *
     * Returns an array of configuration options for the module.
     *
     * @return array The array of available options.
     */
    private function getAllOptions(): array
    {
        return [
            'general' => [
                [
                    'IDENTIFIER_USER_PROPERTY_LEAD_CODE',
                    'Символьный код пользовательского свойства для проверки на маску',
                    '',
                    ['text']
                ],
            ],
        ];
    }

    /**
     * Renders the settings form with tabs and options.
     *
     * Displays the settings page and processes form submissions.
     */
    public function renderForm(): void
    {
        global $APPLICATION;

        // Create a new tab control for rendering settings
        $tabControl = new CAdminTabControl("tabControl", $this->tabs);

        // Handle form submission and save settings
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_REQUEST['apply'] !== "" && check_bitrix_sessid()) {
            foreach ($this->tabs as $tab) {
                __AdmSettingsSaveOptions($this->moduleId, $tab['OPTIONS']);
            }

            // Redirect to the settings page after saving
            LocalRedirect(
                $APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID .
                '&mid_menu=1&mid=' . urlencode($this->moduleId) .
                '&tabControl_active_tab=' . urlencode($_REQUEST['tabControl_active_tab'])
            );
        }

        // Start rendering the tabs
        $tabControl->Begin();
        ?>
        <form action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= $this->moduleId ?>&lang=<?= LANG ?>" method="post">
            <?php
            // Render each tab with the respective options
            foreach ($this->tabs as $tab) {
                if (!empty($tab["OPTIONS"])) {
                    $tabControl->BeginNextTab();
                    __AdmSettingsDrawList($this->moduleId, $tab["OPTIONS"]);
                }
            }
            // Render form buttons
            $tabControl->Buttons();
            ?>
            <input type="submit" name="apply" value="<?= Loc::GetMessage("COMPANY_CARS_INPUT_APPLY") ?>"
                   class="adm-btn-save"/>
            <input type="reset" name="reset" value="<?= Loc::GetMessage("COMPANY_CARS_INPUT_RESET") ?>">
            <?= bitrix_sessid_post() ?>
        </form>
        <?php
        // End rendering the tabs
        $tabControl->End();
    }
}

// Get the module ID from the request parameters
$request = Application::getInstance()->getContext()->getRequest();
$moduleId = $request->get("mid") ?: $request->get("id");

// Initialize and render the settings form
$settings = new ModuleSettings($moduleId);
$settings->renderForm();

