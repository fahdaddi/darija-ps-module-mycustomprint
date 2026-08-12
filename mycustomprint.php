<?php
/**
 * Custom Print Studio — quote-request module for Darija Stile.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/CustomPrintRequest.php';

class MyCustomPrint extends Module
{
    const CONFIG_BLANKS = 'MYCUSTOMPRINT_BLANKS';
    const CONFIG_METHODS = 'MYCUSTOMPRINT_METHODS';
    const CONFIG_BACK_PLACEMENT_FEE = 'MYCUSTOMPRINT_BACK_FEE';
    const CONFIG_CURRENCY_LABEL = 'MYCUSTOMPRINT_CURRENCY';
    const CONFIG_NOTIFY_EMAIL = 'MYCUSTOMPRINT_NOTIFY_EMAIL';

    const DEFAULT_BLANKS = [
        ['id' => 'tee', 'label' => 'Classic tee', 'base' => 189, 'note' => '220gsm combed cotton'],
        ['id' => 'oversized', 'label' => 'Oversized tee', 'base' => 239, 'note' => 'Boxy cut, dropped shoulder'],
        ['id' => 'hoodie', 'label' => 'Hoodie', 'base' => 399, 'note' => '350gsm brushed fleece'],
        ['id' => 'mug', 'label' => 'Mug', 'base' => 89, 'note' => 'Ceramic, 330ml'],
    ];

    const DEFAULT_METHODS = [
        ['id' => 'dtf', 'label' => 'DTF transfer', 'note' => 'Any artwork, photographic detail', 'fee' => 0],
        ['id' => 'sub', 'label' => 'Sublimation', 'note' => 'Edge-to-edge, light fabrics only', 'fee' => 25],
        ['id' => 'embro', 'label' => 'Embroidery', 'note' => 'Line art and text only', 'fee' => 60],
    ];

    public function __construct()
    {
        $this->name = 'mycustomprint';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Darija Stile';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0', 'max' => '9.99.99'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Custom Print Studio');
        $this->description = $this->l('Quote-request studio for custom-printed products (upload artwork, pick a blank, placement and print method, get a price estimate).');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall? All submitted print requests will be deleted.');
    }

    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->registerHook('moduleRoutes')
            && Configuration::updateValue(self::CONFIG_BLANKS, json_encode(self::DEFAULT_BLANKS))
            && Configuration::updateValue(self::CONFIG_METHODS, json_encode(self::DEFAULT_METHODS))
            && Configuration::updateValue(self::CONFIG_BACK_PLACEMENT_FEE, 20)
            && Configuration::updateValue(self::CONFIG_CURRENCY_LABEL, 'MAD')
            && Configuration::updateValue(self::CONFIG_NOTIFY_EMAIL, Configuration::get('PS_SHOP_EMAIL'));
    }

    public function uninstall()
    {
        return $this->uninstallDb()
            && Configuration::deleteByName(self::CONFIG_BLANKS)
            && Configuration::deleteByName(self::CONFIG_METHODS)
            && Configuration::deleteByName(self::CONFIG_BACK_PLACEMENT_FEE)
            && Configuration::deleteByName(self::CONFIG_CURRENCY_LABEL)
            && Configuration::deleteByName(self::CONFIG_NOTIFY_EMAIL)
            && parent::uninstall();
    }

    protected function installDb()
    {
        return Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'mycustomprint_request` (
                `id_mycustomprint_request` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_customer` INT UNSIGNED NULL,
                `firstname` VARCHAR(255) NOT NULL,
                `lastname` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `phone` VARCHAR(32) NULL,
                `blank` VARCHAR(64) NOT NULL,
                `placement` VARCHAR(64) NOT NULL,
                `method` VARCHAR(64) NOT NULL,
                `size` VARCHAR(16) NULL,
                `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
                `estimated_total` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `brief` TEXT NULL,
                `artwork_path` VARCHAR(255) NULL,
                `status` VARCHAR(32) NOT NULL DEFAULT "new",
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_mycustomprint_request`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;
        ');
    }

    protected function uninstallDb()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mycustomprint_request`');
    }

    /**
     * Clean URL for the studio page.
     */
    public function hookModuleRoutes($params)
    {
        return [
            'module-mycustomprint-studio' => [
                'controller' => 'studio',
                'rule' => 'customize',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'mycustomprint',
                ],
            ],
        ];
    }

    /**
     * Admin configuration screen: notify email + editable blanks/methods lists.
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitMyCustomPrintSettings')) {
            $output .= $this->processSettingsSubmit();
        }

        return $output . $this->renderSettingsForm();
    }

    protected function processSettingsSubmit()
    {
        $notifyEmail = trim((string) Tools::getValue('notify_email'));
        if (!Validate::isEmail($notifyEmail)) {
            return $this->displayError($this->l('Please enter a valid notification email address.'));
        }

        $blanks = [];
        for ($i = 0; $i < 6; $i++) {
            $label = trim((string) Tools::getValue('blank_label_' . $i));
            if ($label === '') {
                continue;
            }
            $blanks[] = [
                'id' => Tools::getValue('blank_id_' . $i) ?: Tools::str2url($label),
                'label' => $label,
                'base' => (float) Tools::getValue('blank_base_' . $i),
                'note' => trim((string) Tools::getValue('blank_note_' . $i)),
            ];
        }

        $methods = [];
        for ($i = 0; $i < 6; $i++) {
            $label = trim((string) Tools::getValue('method_label_' . $i));
            if ($label === '') {
                continue;
            }
            $methods[] = [
                'id' => Tools::getValue('method_id_' . $i) ?: Tools::str2url($label),
                'label' => $label,
                'note' => trim((string) Tools::getValue('method_note_' . $i)),
                'fee' => (float) Tools::getValue('method_fee_' . $i),
            ];
        }

        if (empty($blanks)) {
            return $this->displayError($this->l('At least one blank product is required.'));
        }
        if (empty($methods)) {
            return $this->displayError($this->l('At least one print method is required.'));
        }

        Configuration::updateValue(self::CONFIG_NOTIFY_EMAIL, $notifyEmail);
        Configuration::updateValue(self::CONFIG_BACK_PLACEMENT_FEE, (float) Tools::getValue('back_placement_fee'));
        Configuration::updateValue(self::CONFIG_CURRENCY_LABEL, trim((string) Tools::getValue('currency_label')) ?: 'MAD');
        Configuration::updateValue(self::CONFIG_BLANKS, json_encode($blanks));
        Configuration::updateValue(self::CONFIG_METHODS, json_encode($methods));

        return $this->displayConfirmation($this->l('Settings updated.'));
    }

    protected function renderSettingsForm()
    {
        $blanks = json_decode(Configuration::get(self::CONFIG_BLANKS), true) ?: self::DEFAULT_BLANKS;
        $methods = json_decode(Configuration::get(self::CONFIG_METHODS), true) ?: self::DEFAULT_METHODS;

        $this->context->smarty->assign([
            'notify_email' => Configuration::get(self::CONFIG_NOTIFY_EMAIL),
            'back_placement_fee' => Configuration::get(self::CONFIG_BACK_PLACEMENT_FEE),
            'currency_label' => Configuration::get(self::CONFIG_CURRENCY_LABEL),
            'blanks' => array_pad($blanks, 6, ['id' => '', 'label' => '', 'base' => '', 'note' => '']),
            'methods' => array_pad($methods, 6, ['id' => '', 'label' => '', 'note' => '', 'fee' => '']),
            'form_action' => $this->getModuleConfigurationPageLink(),
        ]);

        return $this->context->smarty->fetch($this->getLocalPath() . 'views/templates/admin/configure.tpl');
    }

    /**
     * Builds the "save settings" form action for the admin configure screen.
     * PS 9's admin is Symfony-routed, so AdminController::$currentIndex already
     * holds the full current request path — appending query params to it (the
     * legacy PS 1.6 pattern) produces a doubled/invalid path once the router
     * re-resolves it. Link::getAdminLink() is the version-safe way to build
     * this: it bridges legacy controller+params into a proper routed URL.
     */
    protected function getModuleConfigurationPageLink()
    {
        return $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => $this->name,
            'tab_module' => $this->tab,
            'module_name' => $this->name,
        ]);
    }

    /**
     * Convenience accessors used by the front controller.
     */
    public function getBlanks()
    {
        return json_decode(Configuration::get(self::CONFIG_BLANKS), true) ?: self::DEFAULT_BLANKS;
    }

    public function getMethods()
    {
        return json_decode(Configuration::get(self::CONFIG_METHODS), true) ?: self::DEFAULT_METHODS;
    }
}
