<?php
/**
 * Dedicated back-office menu entry for the Custom Print Studio settings
 * screen, so it's reachable from the admin menu (under Modules) and not
 * only via the Module Manager's Configure button. Delegates entirely to
 * MyCustomPrint::getContent(), which already builds and processes the
 * settings form — this controller only provides the menu-page shell.
 *
 * @property MyCustomPrint $module
 */
class AdminMyCustomPrintSettingsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        if (!$this->viewAccess()) {
            $this->errors[] = $this->trans('You do not have permission to view this.', [], 'Admin.Notifications.Error');

            return;
        }

        $this->content .= $this->module->getContent();

        $this->context->smarty->assign([
            'content' => $this->content,
        ]);
    }
}
