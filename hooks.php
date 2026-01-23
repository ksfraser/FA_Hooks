<?php

// FrontAccounting hooks file for the FA-Hooks module.
// When installed under FA as modules/fa-hooks, this registers the module.

class hooks_fa_hooks extends hooks
{
    var $module_name = 'FA Hooks';

    function install()
    {
        // FA-Hooks is a library module - no database setup needed
        // It provides the hook system for other modules to use
        return true;
    }

    function install_access()
    {
        // No special access requirements - this is a library module
        return true;
    }

    function activate()
    {
        // Ensure the hook system is available globally
        global $path_to_root;

        $hooks_path = $path_to_root . '/modules/fa-hooks/src/Ksfraser/FA_Hooks/HookManager.php';
        if (file_exists($hooks_path)) {
            // Initialize global hook manager if not already done
            if (!isset($GLOBALS['fa_hooks_manager'])) {
                require_once $hooks_path;
                $GLOBALS['fa_hooks_manager'] = new Ksfraser\FA_Hooks\HookManager();
            }
        }

        return true;
    }

    function deactivate()
    {
        // Clean up global reference
        if (isset($GLOBALS['fa_hooks_manager'])) {
            unset($GLOBALS['fa_hooks_manager']);
        }

        return true;
    }
}