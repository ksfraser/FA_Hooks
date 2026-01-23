# FA_Hooks

A lightweight, extensible hook system for FrontAccounting module extensions, providing WordPress/SuiteCRM-style hooks for extending core FA functionality without modifying core files.

## Overview

FA-Hooks is designed to be installed as a **separate FA module** that provides a generic hook system for ALL your other FA modules. This enables cross-module integration and extensibility.

### Key Features

- **Priority-based hook execution** - Control the order of hook callbacks
- **Exception handling** - Continues execution even if individual hooks fail
- **Hook data storage** - Store and retrieve data between hooks
- **Multiple callback support** - Register multiple callbacks per hook
- **Dynamic hook registration** - Modules can register their own hook points
- **Version abstraction** - Automatic handling of FA version differences
- **Type-safe containers** - Object-oriented data management
- **Cross-module integration** - Extensions can modify other modules' behavior

## Installation

### As an FA Module (Recommended for Multi-Module Setups)

For use with multiple FA modules, install FA-Hooks as a separate module:

```bash
# Install in FA modules directory
cd /path/to/frontaccounting/modules
git clone https://github.com/ksfraser/FA_Hooks.git fa-hooks

# Install dependencies
cd fa-hooks
composer install

# Activate in FA admin (Setup → Install/Update Modules)
```

This makes the hook system available to ALL your FA modules.

### As a Library

For single-module use or development:

```bash
# Clone the repository
git clone https://github.com/ksfraser/FA_Hooks.git

# Install dependencies
composer install
```

Then include in your project:

```php
require_once '/path/to/fa-hooks/src/Ksfraser/FA_Hooks/HookManager.php';
$hooks = new Ksfraser\FA_Hooks\HookManager();
```

## Usage

### Basic Hook Registration

```php
use Ksfraser\FA_Hooks\HookManager;

// Get the global hook manager instance
$hooks = new HookManager();

// Register a callback
$hooks->add_hook('my_hook', function($param) {
    echo "Hook called with: $param";
    return $param;
});

// Call the hook
$result = $hooks->call_hook('my_hook', 'Hello World');
```

### Priority-based Execution

```php
// Higher priority numbers execute later
$hooks->add_hook('save_data', 'validate_data', 10);
$hooks->add_hook('save_data', 'sanitize_data', 5);  // Executes first
$hooks->add_hook('save_data', 'log_data', 20);      // Executes last
```

### Filter Hooks (with return values)

```php
$hooks->add_hook('filter_content', function($content) {
    return strtoupper($content);
});

$filtered = $hooks->call_hook('filter_content', 'hello world');
// Result: "HELLO WORLD"
```

### Action Hooks (no return values)

```php
$hooks->add_hook('user_login', function($user_id) {
    // Log the login
    error_log("User $user_id logged in");
});

$hooks->call_hook('user_login', 123);
// No return value expected
```

## FrontAccounting Integration

This library is designed to work with FrontAccounting's module system:

```php
// In your module's hooks.php
class hooks_my_module extends hooks {
    function register_hooks() {
        $hooks = fa_hooks(); // Global FA hook manager
        $hooks->add_hook('pre_item_write', [$this, 'my_save_handler']);
        $hooks->add_hook('pre_item_delete', [$this, 'my_delete_handler']);
    }
}
```

## API Reference

### HookManager

#### `add_hook(string $hook_name, callable $callback, int $priority = 10): void`
Register a callback for a hook.

#### `remove_hook(string $hook_name, callable $callback): void`
Remove a callback from a hook.

#### `call_hook(string $hook_name, ...$args): mixed`
Execute all callbacks for a hook. Returns the filtered value or null.

#### `has_hook(string $hook_name): bool`
Check if a hook has registered callbacks.

#### `set_hook_data(string $key, $value): void`
Store data for hooks to access.

#### `get_hook_data(string $key, $default = null): mixed`
Retrieve stored hook data.

#### `clear(): void`
Clear all hooks and data.

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test
# or
./vendor/bin/phpunit
```

## License

MIT License - see LICENSE file for details.