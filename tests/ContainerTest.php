<?php

namespace Ksfraser\FA_Hooks\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA_Hooks\ArrayContainer;
use Ksfraser\FA_Hooks\TabContainer;
use Ksfraser\FA_Hooks\MenuContainer;
use Ksfraser\FA_Hooks\ContainerFactory;
use Ksfraser\FA_Hooks\HookRegistry;
use Ksfraser\FA_Hooks\FAVersionAdapter;
use Ksfraser\FA_Hooks\TabDefinition;

/**
 * Test suite for container classes and hook registry
 */
class ContainerTest extends TestCase
{
    private $versionAdapter;

    protected function setUp(): void
    {
        $this->versionAdapter = new FAVersionAdapter();
    }

    public function testArrayContainerBasicFunctionality()
    {
        $container = new ArrayContainer($this->versionAdapter);

        // Test adding items
        $container->addItem('key1', 'value1');
        $container->addItem('key2', ['nested' => 'value']);

        $result = $container->toArray();
        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals(['nested' => 'value'], $result['key2']);
    }

    public function testArrayContainerMergeWith()
    {
        $container = new ArrayContainer($this->versionAdapter);
        $container->addItem('key1', 'value1');

        $existing = ['key2' => 'existing_value'];
        $result = $container->mergeWith($existing);

        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals('existing_value', $result['key2']);
    }

    public function testTabContainer()
    {
        $container = new TabContainer($this->versionAdapter);

        $tab = new TabDefinition('Test Tab', 'test.php', $this->versionAdapter);
        $container->addItem('test_tab', $tab);

        $result = $container->toArray();
        $this->assertArrayHasKey('test_tab', $result);
        $this->assertIsArray($result['test_tab']);
    }

    public function testTabContainerCreateTab()
    {
        $container = new TabContainer($this->versionAdapter);
        $container->createTab('new_tab', 'New Tab', 'new.php', ['icon' => 'icon.png']);

        $result = $container->toArray();
        $this->assertArrayHasKey('new_tab', $result);
        $this->assertEquals('New Tab', $result['new_tab']['title']);
        $this->assertEquals('new.php', $result['new_tab']['url']);
    }

    public function testMenuContainer()
    {
        $container = new MenuContainer($this->versionAdapter);

        $menuItem = [
            'title' => 'Test Menu',
            'url' => 'test.php',
            'access' => 'SA_ITEM',
            'icon' => 'icon.png'
        ];

        $container->addItem('test_menu', $menuItem);

        $result = $container->toArray();
        $this->assertArrayHasKey('test_menu', $result);
        $this->assertEquals($menuItem, $result['test_menu']);
    }

    public function testMenuContainerCreateMenuItem()
    {
        $container = new MenuContainer($this->versionAdapter);
        $container->createMenuItem('new_menu', 'New Menu', 'new.php', ['access' => 'SA_ITEM']);

        $result = $container->toArray();
        $this->assertArrayHasKey('new_menu', $result);
        $this->assertEquals('New Menu', $result['new_menu']['title']);
        $this->assertEquals('new.php', $result['new_menu']['url']);
        $this->assertEquals('SA_ITEM', $result['new_menu']['access']);
    }

    public function testContainerFactory()
    {
        $factory = new ContainerFactory($this->versionAdapter);

        $tabContainer = $factory->createTabContainer();
        $this->assertInstanceOf(TabContainer::class, $tabContainer);

        $menuContainer = $factory->createMenuContainer();
        $this->assertInstanceOf(MenuContainer::class, $menuContainer);

        $arrayContainer = $factory->createArrayContainer();
        $this->assertInstanceOf(ArrayContainer::class, $arrayContainer);
    }

    public function testContainerFactoryCreateByType()
    {
        $factory = new ContainerFactory($this->versionAdapter);

        $tabContainer = $factory->createContainer('tab');
        $this->assertInstanceOf(TabContainer::class, $tabContainer);

        $menuContainer = $factory->createContainer('menu');
        $this->assertInstanceOf(MenuContainer::class, $menuContainer);

        $arrayContainer = $factory->createContainer('array');
        $this->assertInstanceOf(ArrayContainer::class, $arrayContainer);
    }

    public function testContainerFactoryInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $factory = new ContainerFactory($this->versionAdapter);
        $factory->createContainer('invalid_type');
    }

    public function testHookRegistryBasicFunctionality()
    {
        $registry = new HookRegistry($this->versionAdapter);

        $executed = false;
        $registry->registerHookPoint('test_hook', 'test_module', function() use (&$executed) {
            $executed = true;
            return 'default_result';
        });

        $result = $registry->executeHook('test_hook');
        $this->assertTrue($executed);
        $this->assertEquals('default_result', $result);
    }

    public function testHookRegistryWithExtensions()
    {
        $registry = new HookRegistry($this->versionAdapter);

        $executionOrder = [];
        $registry->registerHookPoint('test_hook', 'test_module', function() use (&$executionOrder) {
            $executionOrder[] = 'default';
            return 'default_result';
        });

        $registry->registerExtension('test_hook', 'ext_module1', function() use (&$executionOrder) {
            $executionOrder[] = 'ext1';
        }, 5);

        $registry->registerExtension('test_hook', 'ext_module2', function() use (&$executionOrder) {
            $executionOrder[] = 'ext2';
        }, 10);

        $result = $registry->executeHook('test_hook');
        $this->assertEquals(['ext1', 'ext2', 'default'], $executionOrder);
        $this->assertEquals('default_result', $result);
    }

    public function testHookRegistryUnregisteredHook()
    {
        $this->expectException(\InvalidArgumentException::class);
        $registry = new HookRegistry($this->versionAdapter);
        $registry->executeHook('nonexistent_hook');
    }

    public function testHookRegistryExtensionForUnregisteredHook()
    {
        $this->expectException(\InvalidArgumentException::class);
        $registry = new HookRegistry($this->versionAdapter);
        $registry->registerExtension('nonexistent_hook', 'ext_module', function() {});
    }

    public function testHookRegistryPriorityOrdering()
    {
        $registry = new HookRegistry($this->versionAdapter);

        $executionOrder = [];
        $registry->registerHookPoint('test_hook', 'test_module', function() use (&$executionOrder) {
            $executionOrder[] = 'default';
        });

        $registry->registerExtension('test_hook', 'ext_high', function() use (&$executionOrder) {
            $executionOrder[] = 'high_priority';
        }, 1);

        $registry->registerExtension('test_hook', 'ext_low', function() use (&$executionOrder) {
            $executionOrder[] = 'low_priority';
        }, 20);

        $registry->registerExtension('test_hook', 'ext_medium', function() use (&$executionOrder) {
            $executionOrder[] = 'medium_priority';
        }, 10);

        $registry->executeHook('test_hook');
        $this->assertEquals(['high_priority', 'medium_priority', 'low_priority', 'default'], $executionOrder);
    }

    public function testHookRegistryMetadata()
    {
        $registry = new HookRegistry($this->versionAdapter);

        $metadata = ['description' => 'Test hook', 'version' => '1.0'];
        $registry->registerHookPoint('test_hook', 'test_module', function() {}, $metadata);

        $hookPoint = $registry->getHookPoint('test_hook');
        $this->assertEquals($metadata, $hookPoint['metadata']);
        $this->assertEquals('test_module', $hookPoint['module']);
    }

    public function testHookRegistryGetHookPoints()
    {
        $registry = new HookRegistry($this->versionAdapter);

        $registry->registerHookPoint('hook1', 'module1', function() {});
        $registry->registerHookPoint('hook2', 'module2', function() {});

        $hookPoints = $registry->getHookPoints();
        $this->assertContains('hook1', $hookPoints);
        $this->assertContains('hook2', $hookPoints);
        $this->assertCount(2, $hookPoints);
    }

    public function testInvalidTabContainerInput()
    {
        $this->expectException(\InvalidArgumentException::class);
        $container = new TabContainer($this->versionAdapter);
        $container->addItem('invalid', 'not_a_tab_definition');
    }

    public function testInvalidMenuContainerInput()
    {
        $this->expectException(\InvalidArgumentException::class);
        $container = new MenuContainer($this->versionAdapter);
        $container->addItem('invalid', 'not_an_array');
    }
}