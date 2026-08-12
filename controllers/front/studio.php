<?php
/**
 * Custom Print Studio front page: "Print your own" quote-request flow.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MyCustomPrintStudioModuleFrontController extends ModuleFrontController
{
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'svg', 'pdf', 'ai'];
    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/svg+xml',
        'application/pdf',
        'application/postscript',
        'application/illustrator',
        // Some servers report AI/EPS as generic octet-stream; the extension
        // allowlist above is the primary gate, this is a secondary check.
        'application/octet-stream',
    ];
    const MAX_FILE_SIZE = 41943040; // 40 MB, matches the studio copy

    public $ssl = true;

    private $sentOk = false;
    private $formErrors = [];
    private $uploadError = null;

    public function setMedia()
    {
        parent::setMedia();
        $this->context->controller->registerStylesheet(
            'mycustomprint-front',
            'modules/' . $this->module->name . '/views/css/front.css'
        );
        $this->context->controller->registerJavascript(
            'mycustomprint-front',
            'modules/' . $this->module->name . '/views/js/front.js'
        );
    }

    public function postProcess()
    {
        if ($this->postExceedsPhpUploadLimit()) {
            $this->formErrors[] = $this->module->l('That file is too large for this server to accept. Try a smaller file or describe it in the brief instead.');

            return;
        }

        if (!Tools::isSubmit('submitPrintRequest')) {
            return;
        }

        // isTokenValid() only meaningfully protects logged-in sessions (its token
        // includes the customer's id/password hash); this form is guest-accessible,
        // so we check against the same page-scoped token the template rendered
        // via the auto-assigned {$token} var (Tools::getToken(), $page=true).
        if (Configuration::get('PS_TOKEN_ENABLE') && strcasecmp(Tools::getToken(), (string) Tools::getValue('token')) !== 0) {
            $this->formErrors[] = $this->module->l('Your session has expired. Please refresh the page and try again.');

            return;
        }

        $this->formErrors = $this->validateSubmission();

        if (!empty($this->formErrors)) {
            return;
        }

        $artworkPath = $this->handleUpload();
        if ($this->hasUploadError()) {
            return;
        }

        $request = new CustomPrintRequest();
        $request->id_customer = (int) $this->context->customer->id ?: null;
        $request->firstname = Tools::getValue('firstname');
        $request->lastname = Tools::getValue('lastname');
        $request->email = Tools::getValue('email');
        $request->phone = Tools::getValue('phone');
        $request->blank = Tools::getValue('blank');
        $request->placement = Tools::getValue('placement');
        $request->method = Tools::getValue('method');
        $request->size = Tools::getValue('size');
        $request->quantity = max(1, (int) Tools::getValue('quantity'));
        $request->estimated_total = (float) Tools::getValue('estimated_total');
        $request->brief = Tools::getValue('brief');
        $request->artwork_path = $artworkPath;
        $request->status = 'new';

        if (!$request->add()) {
            $this->formErrors[] = $this->module->l('Something went wrong saving your request. Please try again.');

            return;
        }

        $this->sendNotification($request);

        Tools::redirect($this->context->link->getModuleLink($this->module->name, 'studio', ['sent' => 1]));
    }

    /**
     * When a POST body exceeds php.ini's post_max_size, PHP silently empties
     * $_POST and $_FILES rather than raising a catchable error — Tools::isSubmit()
     * then just looks like the form wasn't submitted at all, and the customer
     * gets no feedback. CONTENT_LENGTH still reflects the real request size,
     * so a submit attempt with an empty $_POST but a body larger than the
     * configured limit is diagnostic of exactly this case.
     */
    private function postExceedsPhpUploadLimit()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($_POST)) {
            return false;
        }

        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

        return $contentLength > 0 && $contentLength > Tools::getMaxUploadSize();
    }

    private function hasUploadError()
    {
        if ($this->uploadError !== null) {
            $this->formErrors[] = $this->uploadError;

            return true;
        }

        return false;
    }

    private function validateSubmission()
    {
        $errors = [];

        $blankIds = array_column($this->module->getBlanks(), 'id');
        $methodIds = array_column($this->module->getMethods(), 'id');

        if (!in_array(Tools::getValue('blank'), $blankIds, true)) {
            $errors[] = $this->module->l('Please choose a valid blank product.');
        }
        if (!in_array(Tools::getValue('method'), $methodIds, true)) {
            $errors[] = $this->module->l('Please choose a valid print method.');
        }
        if (Tools::getValue('placement') === '') {
            $errors[] = $this->module->l('Please choose a placement.');
        }
        if (!Validate::isName(Tools::getValue('firstname')) || !Validate::isName(Tools::getValue('lastname'))) {
            $errors[] = $this->module->l('Please enter your first and last name.');
        }
        if (!Validate::isEmail(Tools::getValue('email'))) {
            $errors[] = $this->module->l('Please enter a valid email address.');
        }
        if ((int) Tools::getValue('quantity') < 1) {
            $errors[] = $this->module->l('Quantity must be at least 1.');
        }

        $hasFile = !empty($_FILES['artwork']['tmp_name']) && $_FILES['artwork']['error'] === UPLOAD_ERR_OK;
        $hasBrief = trim((string) Tools::getValue('brief')) !== '';
        if (!$hasFile && !$hasBrief) {
            $errors[] = $this->module->l('Upload your artwork or describe what you want in the brief.');
        }

        return $errors;
    }

    /**
     * Validates and moves the uploaded artwork into the module's
     * access-denied upload directory. Returns the stored filename, or null
     * if no file was submitted. Sets $this->uploadError on failure.
     */
    private function handleUpload()
    {
        if (empty($_FILES['artwork']['tmp_name']) || $_FILES['artwork']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($_FILES['artwork']['error'] !== UPLOAD_ERR_OK) {
            $this->uploadError = $this->module->l('The file could not be uploaded. Please try again.');

            return null;
        }

        $effectiveLimit = min(self::MAX_FILE_SIZE, Tools::getMaxUploadSize());
        if ($_FILES['artwork']['size'] > $effectiveLimit) {
            $this->uploadError = sprintf(
                $this->module->l('That file is too large. The limit on this server is %s.'),
                $this->formatBytes($effectiveLimit)
            );

            return null;
        }

        $originalName = $_FILES['artwork']['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            $this->uploadError = $this->module->l('Unsupported file type. Use PNG, JPG, SVG, PDF or AI.');

            return null;
        }

        if (!is_uploaded_file($_FILES['artwork']['tmp_name'])) {
            $this->uploadError = $this->module->l('The file could not be uploaded. Please try again.');

            return null;
        }

        // Secondary check: real content-based MIME type, not the client-supplied one.
        // Same finfo-based detection PrestaShop core uses in ImageManager::getMimeType().
        $realMime = $this->detectMimeType($_FILES['artwork']['tmp_name']);
        if ($realMime && !in_array($realMime, self::ALLOWED_MIME_TYPES, true)) {
            $this->uploadError = $this->module->l('Unsupported file type. Use PNG, JPG, SVG, PDF or AI.');

            return null;
        }

        $safeBaseName = Tools::str2url(pathinfo($originalName, PATHINFO_FILENAME));
        if ($safeBaseName === '') {
            $safeBaseName = 'artwork';
        }
        $filename = $safeBaseName . '_' . uniqid('', true) . '.' . $ext;
        $uploadDir = _PS_MODULE_DIR_ . $this->module->name . '/upload/';

        if (!move_uploaded_file($_FILES['artwork']['tmp_name'], $uploadDir . $filename)) {
            $this->uploadError = $this->module->l('The file could not be saved. Please try again.');

            return null;
        }

        return $filename;
    }

    private function formatBytes($bytes)
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1024) . ' KB';
    }

    /**
     * Content-based MIME detection, mirroring the fallback chain PrestaShop
     * core uses in ImageManager::getMimeType() (finfo, then mime_content_type).
     * Returns null when detection isn't possible, so callers can treat that
     * as "inconclusive" rather than "rejected" — the extension allowlist in
     * handleUpload() is the primary gate.
     */
    private function detectMimeType($filename)
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

    private function sendNotification(CustomPrintRequest $request)
    {
        $notifyEmail = Configuration::get(MyCustomPrint::CONFIG_NOTIFY_EMAIL);
        if (!$notifyEmail) {
            return;
        }

        $blankLabel = $request->blank;
        foreach ($this->module->getBlanks() as $blank) {
            if ($blank['id'] === $request->blank) {
                $blankLabel = $blank['label'];
                break;
            }
        }

        $methodLabel = $request->method;
        foreach ($this->module->getMethods() as $method) {
            if ($method['id'] === $request->method) {
                $methodLabel = $method['label'];
                break;
            }
        }

        if ($request->artwork_path) {
            $downloadUrl = $this->context->link->getModuleLink($this->module->name, 'studio', [
                'download' => $request->id,
                'token' => $this->downloadToken($request),
            ]);
            $artworkBlock = '<a href="' . $downloadUrl . '" style="display:inline-block;background:#0a0a0a;color:#ffffff;'
                . 'text-decoration:none;padding:12px 22px;font-size:13px;letter-spacing:0.5px;text-transform:uppercase;">'
                . 'Download artwork</a>';
            $artworkBlockText = $downloadUrl;
        } else {
            $artworkBlock = '<em>No file uploaded — see brief above.</em>';
            $artworkBlockText = 'No file uploaded — see brief above.';
        }

        // Mail::send($idLang, $template, $subject, $templateVars, $to, $toName,
        //     $from, $fromName, $fileAttachment, $mode_smtp, $templatePath, $die,
        //     $idShop, $bcc, $replyTo, $replyToName)
        Mail::send(
            (int) $this->context->language->id,
            'quote_request',
            'New custom print request — ' . $blankLabel,
            [
                '{firstname}' => $request->firstname,
                '{lastname}' => $request->lastname,
                '{email}' => $request->email,
                '{phone}' => $request->phone,
                '{blank}' => $blankLabel,
                '{placement}' => $request->placement,
                '{method}' => $methodLabel,
                '{size}' => $request->size,
                '{quantity}' => $request->quantity,
                '{estimated_total}' => number_format($request->estimated_total, 2) . ' ' . Configuration::get(MyCustomPrint::CONFIG_CURRENCY_LABEL),
                '{brief}' => nl2br(Tools::safeOutput($request->brief)),
                '{artwork_block}' => $artworkBlock,
                '{artwork_block_text}' => $artworkBlockText,
            ],
            $notifyEmail,
            null,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . $this->module->name . '/mails/',
            false,
            (int) $this->context->shop->id
        );
    }

    /**
     * Lightweight, non-guessable token gating artwork downloads, derived
     * from the shop's cookie key so it can't be forged without server secrets.
     */
    private function downloadToken(CustomPrintRequest $request)
    {
        return substr(Tools::hash($request->id . '|' . $request->artwork_path), 0, 32);
    }

    private function handleDownload()
    {
        $id = (int) Tools::getValue('download');
        if (!$id) {
            return;
        }

        $request = new CustomPrintRequest($id);
        if (!Validate::isLoadedObject($request) || !$request->artwork_path) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        $token = Tools::getValue('token');
        if (!$token || !hash_equals($this->downloadToken($request), $token)) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        $filePath = _PS_MODULE_DIR_ . $this->module->name . '/upload/' . basename($request->artwork_path);
        if (!file_exists($filePath)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($request->artwork_path) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function init()
    {
        parent::init();

        if (Tools::getValue('download')) {
            $this->handleDownload();
        }
    }

    public function initContent()
    {
        parent::initContent();

        $this->sentOk = (bool) Tools::getValue('sent');

        $this->context->smarty->assign([
            'blanks' => $this->module->getBlanks(),
            'methods' => $this->module->getMethods(),
            'placements' => [
                ['id' => 'front', 'label' => 'Front chest'],
                ['id' => 'back', 'label' => 'Full back'],
                ['id' => 'pocket', 'label' => 'Pocket'],
                ['id' => 'sleeve', 'label' => 'Sleeve'],
            ],
            'back_placement_fee' => (float) Configuration::get(MyCustomPrint::CONFIG_BACK_PLACEMENT_FEE),
            'currency_label' => Configuration::get(MyCustomPrint::CONFIG_CURRENCY_LABEL),
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
            'module_dir' => $this->module->getPathUri(),
            'max_upload_label' => $this->formatBytes(min(self::MAX_FILE_SIZE, Tools::getMaxUploadSize())),
            'sent_ok' => $this->sentOk,
            'form_errors' => $this->formErrors,
            'form_action' => $this->context->link->getModuleLink($this->module->name, 'studio'),
        ]);

        $this->setTemplate('module:mycustomprint/views/templates/front/studio.tpl');
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = [
            'title' => $this->module->l('Print your own'),
            'url' => $this->context->link->getModuleLink($this->module->name, 'studio'),
        ];

        return $breadcrumb;
    }
}
