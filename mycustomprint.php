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
        ['id' => 'tee', 'label' => 'Classic tee', 'base' => 189, 'note' => '220gsm combed cotton', 'image_front' => '', 'image_back' => ''],
        ['id' => 'oversized', 'label' => 'Oversized tee', 'base' => 239, 'note' => 'Boxy cut, dropped shoulder', 'image_front' => '', 'image_back' => ''],
        ['id' => 'hoodie', 'label' => 'Hoodie', 'base' => 399, 'note' => '350gsm brushed fleece', 'image_front' => '', 'image_back' => ''],
        ['id' => 'mug', 'label' => 'Mug', 'base' => 89, 'note' => 'Ceramic, 330ml', 'image_front' => '', 'image_back' => ''],
    ];

    const BLANK_IMAGE_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    const BLANK_IMAGE_ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    const BLANK_IMAGE_MAX_SIZE = 5242880; // 5 MB

    const DEFAULT_METHODS = [
        ['id' => 'dtf', 'label' => 'DTF transfer', 'note' => 'Any artwork, photographic detail', 'fee' => 0],
        ['id' => 'sub', 'label' => 'Sublimation', 'note' => 'Edge-to-edge, light fabrics only', 'fee' => 25],
        ['id' => 'embro', 'label' => 'Embroidery', 'note' => 'Line art and text only', 'fee' => 60],
    ];

    /**
     * Registers the admin menu entry (Modules > Custom Print Studio) —
     * consumed automatically by ModuleTabRegister during install(), same
     * mechanism psshipping/blockwishlist use for their own settings pages.
     * class_name must match the controller file under controllers/admin/.
     */
    public $tabs = [
        [
            'class_name' => 'AdminMyCustomPrintSettings',
            'visible' => true,
            'name' => 'Custom Print Studio',
            'parent_class_name' => 'AdminParentModulesSf',
        ],
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

    /**
     * views/img/blanks/ holds admin-uploaded blank product photos. Git doesn't
     * track empty directories, so a fresh clone won't have it — create it
     * defensively rather than assuming it survived deployment.
     */
    protected function getBlankImagesDir()
    {
        $dir = $this->getLocalPath() . 'views/img/blanks/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * Validates and stores one uploaded blank photo. Returns the new stored
     * filename, or null if this specific input had no file (leave existing
     * image untouched — this is not an error). Validation failures are
     * appended to $errors by reference and also return null, so a bad
     * upload doesn't silently wipe the existing image.
     */
    protected function handleBlankImageUpload($inputName, array &$errors)
    {
        if (empty($_FILES[$inputName]['tmp_name']) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->l('The file could not be uploaded. Please try again.');

            return null;
        }

        if ($_FILES[$inputName]['size'] > self::BLANK_IMAGE_MAX_SIZE) {
            $errors[] = $this->l('That image is too large. The limit is 5 MB.');

            return null;
        }

        $originalName = $_FILES[$inputName]['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::BLANK_IMAGE_ALLOWED_EXTENSIONS, true)) {
            $errors[] = $this->l('Unsupported image type. Use JPG, PNG or WEBP.');

            return null;
        }

        if (!is_uploaded_file($_FILES[$inputName]['tmp_name'])) {
            $errors[] = $this->l('The file could not be uploaded. Please try again.');

            return null;
        }

        $realMime = $this->detectImageMimeType($_FILES[$inputName]['tmp_name']);
        if ($realMime && !in_array($realMime, self::BLANK_IMAGE_ALLOWED_MIME_TYPES, true)) {
            $errors[] = $this->l('Unsupported image type. Use JPG, PNG or WEBP.');

            return null;
        }

        $safeBaseName = Tools::str2url(pathinfo($originalName, PATHINFO_FILENAME));
        if ($safeBaseName === '') {
            $safeBaseName = 'blank';
        }
        $filename = $safeBaseName . '_' . uniqid('', true) . '.' . $ext;

        if (!move_uploaded_file($_FILES[$inputName]['tmp_name'], $this->getBlankImagesDir() . $filename)) {
            $errors[] = $this->l('The image could not be saved. Please try again.');

            return null;
        }

        return $filename;
    }

    /**
     * Same finfo-based content MIME detection used by the front controller's
     * artwork upload handler (studio.php::detectMimeType) — kept as a
     * separate copy since front and admin controllers don't share a base
     * class here, but the logic is identical.
     */
    protected function detectImageMimeType($filename)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filename);
            finfo_close($finfo);
            if ($mimeType) {
                return $mimeType;
            }
        }

        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filename);
            if ($mimeType) {
                return $mimeType;
            }
        }

        return null;
    }

    protected function deleteBlankImage($filename)
    {
        if ($filename === '') {
            return;
        }
        $path = $this->getBlankImagesDir() . basename($filename);
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function uninstall()
    {
        return $this->uninstallDb()
            && $this->uninstallTab()
            && Configuration::deleteByName(self::CONFIG_BLANKS)
            && Configuration::deleteByName(self::CONFIG_METHODS)
            && Configuration::deleteByName(self::CONFIG_BACK_PLACEMENT_FEE)
            && Configuration::deleteByName(self::CONFIG_CURRENCY_LABEL)
            && Configuration::deleteByName(self::CONFIG_NOTIFY_EMAIL)
            && parent::uninstall();
    }

    /**
     * ModuleTabRegister installs tabs declared in $this->tabs automatically,
     * but core never cleans them up on uninstall — same manual pattern
     * dashgoals.php uses for its own admin tab.
     */
    protected function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminMyCustomPrintSettings');
        if ($idTab) {
            $tab = new Tab($idTab);
            $tab->delete();
        }

        return true;
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

        // Existing blanks, keyed by the same row index the form was rendered
        // with, so an untouched file input keeps its current image instead
        // of clearing it.
        $existingBlanks = array_values(json_decode(Configuration::get(self::CONFIG_BLANKS), true) ?: self::DEFAULT_BLANKS);

        $blanks = [];
        $uploadErrors = [];
        for ($i = 0; $i < 6; $i++) {
            $label = trim((string) Tools::getValue('blank_label_' . $i));
            if ($label === '') {
                continue;
            }

            $imageFront = isset($existingBlanks[$i]['image_front']) ? $existingBlanks[$i]['image_front'] : '';
            $imageBack = isset($existingBlanks[$i]['image_back']) ? $existingBlanks[$i]['image_back'] : '';

            $newFront = $this->handleBlankImageUpload('blank_image_front_' . $i, $uploadErrors);
            if ($newFront !== null) {
                $this->deleteBlankImage($imageFront);
                $imageFront = $newFront;
            }

            $newBack = $this->handleBlankImageUpload('blank_image_back_' . $i, $uploadErrors);
            if ($newBack !== null) {
                $this->deleteBlankImage($imageBack);
                $imageBack = $newBack;
            }

            $blanks[] = [
                'id' => Tools::getValue('blank_id_' . $i) ?: Tools::str2url($label),
                'label' => $label,
                'base' => (float) Tools::getValue('blank_base_' . $i),
                'note' => trim((string) Tools::getValue('blank_note_' . $i)),
                'image_front' => $imageFront,
                'image_back' => $imageBack,
            ];
        }

        if (!empty($uploadErrors)) {
            return $this->displayError(implode('<br>', $uploadErrors));
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
        $blanks = array_values(json_decode(Configuration::get(self::CONFIG_BLANKS), true) ?: self::DEFAULT_BLANKS);
        $methods = json_decode(Configuration::get(self::CONFIG_METHODS), true) ?: self::DEFAULT_METHODS;

        $blanks = array_pad($blanks, 6, ['id' => '', 'label' => '', 'base' => '', 'note' => '', 'image_front' => '', 'image_back' => '']);
        foreach ($blanks as &$blank) {
            $blank['image_front_url'] = $this->getBlankImageUrl($blank['image_front'] ?? '');
            $blank['image_back_url'] = $this->getBlankImageUrl($blank['image_back'] ?? '');
        }
        unset($blank);

        $this->context->smarty->assign([
            'notify_email' => Configuration::get(self::CONFIG_NOTIFY_EMAIL),
            'back_placement_fee' => Configuration::get(self::CONFIG_BACK_PLACEMENT_FEE),
            'currency_label' => Configuration::get(self::CONFIG_CURRENCY_LABEL),
            'blanks' => $blanks,
            'methods' => array_pad($methods, 6, ['id' => '', 'label' => '', 'note' => '', 'fee' => '']),
            'form_action' => $this->getModuleConfigurationPageLink(),
        ]);

        return $this->context->smarty->fetch($this->getLocalPath() . 'views/templates/admin/configure.tpl');
    }

    public function getBlankImageUrl($filename)
    {
        if ($filename === '' || $filename === null) {
            return '';
        }

        return $this->getPathUri() . 'views/img/blanks/' . $filename;
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
