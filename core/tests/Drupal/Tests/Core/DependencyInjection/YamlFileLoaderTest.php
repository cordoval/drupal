<?php

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\YamlFileLoader;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class YamlFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadFile()
    {
        $loader = new YamlFileLoader($container = new ContainerBuilder());
        $r = new \ReflectionObject($loader);
        $m = $r->getMethod('loadFile');
        $m->setAccessible(true);

        foreach (array('nonvalid1', 'nonvalid2') as $fixture) {
            try {
                $m->invoke($loader, __DIR__.'/Fixtures/yaml/'.$fixture.'.yml');
                $this->fail('->loadFile() throws an InvalidArgumentException if the loaded file does not validate');
            } catch (\Exception $e) {
                $this->assertInstanceOf('\InvalidArgumentException', $e, '->load() throws an InvalidArgumentException if the loaded file does not validate');
                if ('nonvalid1' === $fixture) {
                    $this->assertStringMatchesFormat('The service file "%s" is not valid: it contains invalid keys %s. Services have to be added under "services" and Parameters under "parameters".', $e->getMessage(), '->load() throws an InvalidArgumentException if the loaded file does not validate');
                }
                if ('nonvalid2' === $fixture) {
                    $this->assertStringMatchesFormat('The service file "%s" is not valid: it is not an array.', $e->getMessage(), '->load() throws an InvalidArgumentException if the loaded file does not validate');
                }
            }
        }
    }

    public function testLoadParameters()
    {
        $loader = new YamlFileLoader($container = new ContainerBuilder());
        $loader->load(__DIR__.'/Fixtures/yaml/services2.yml');
        $this->assertEquals(array('foo' => 'bar', 'mixedcase' => array('MixedCaseKey' => 'value'), 'values' => array(true, false, 0, 1000.3), 'bar' => 'foo', 'escape' => '@escapeme', 'foo_bar' => new Reference('foo_bar')), $container->getParameterBag()->all(), '->load() converts YAML keys to lowercase');
    }

    public function testLoadServices()
    {
        $loader = new YamlFileLoader($container = new ContainerBuilder());
        $loader->load(__DIR__.'/Fixtures/yaml/services6.yml');
        $services = $container->getDefinitions();
        $this->assertTrue(isset($services['foo']), '->load() parses service elements');
        $this->assertEquals('Symfony\\Component\\DependencyInjection\\Definition', get_class($services['foo']), '->load() converts service element to Definition instances');
        $this->assertEquals('FooClass', $services['foo']->getClass(), '->load() parses the class attribute');
        $this->assertEquals('container', $services['scope.container']->getScope());
        $this->assertEquals('custom', $services['scope.custom']->getScope());
        $this->assertEquals('prototype', $services['scope.prototype']->getScope());
        $this->assertEquals('getInstance', $services['constructor']->getFactoryMethod(), '->load() parses the factory_method attribute');
        $this->assertEquals('%path%/foo.php', $services['file']->getFile(), '->load() parses the file tag');
        $this->assertEquals(array('foo', new Reference('foo'), array(true, false)), $services['arguments']->getArguments(), '->load() parses the argument tags');
        $this->assertEquals('sc_configure', $services['configurator1']->getConfigurator(), '->load() parses the configurator tag');
        $this->assertEquals(array(new Reference('baz'), 'configure'), $services['configurator2']->getConfigurator(), '->load() parses the configurator tag');
        $this->assertEquals(array('BazClass', 'configureStatic'), $services['configurator3']->getConfigurator(), '->load() parses the configurator tag');
        $this->assertEquals(array(array('setBar', array('foo', new Reference('foo'), array(true, false)))), $services['method_call2']->getMethodCalls(), '->load() parses the method_call tag');
        $this->assertEquals('baz_factory', $services['factory_service']->getFactoryService());

        $this->assertTrue($services['request']->isSynthetic(), '->load() parses the synthetic flag');
        $this->assertTrue($services['request']->isSynchronized(), '->load() parses the synchronized flag');
        $this->assertTrue($services['request']->isLazy(), '->load() parses the lazy flag');

        $aliases = $container->getAliases();
        $this->assertTrue(isset($aliases['alias_for_foo']), '->load() parses aliases');
        $this->assertEquals('foo', (string) $aliases['alias_for_foo'], '->load() parses aliases');
        $this->assertTrue($aliases['alias_for_foo']->isPublic());
        $this->assertTrue(isset($aliases['another_alias_for_foo']));
        $this->assertEquals('foo', (string) $aliases['another_alias_for_foo']);
        $this->assertFalse($aliases['another_alias_for_foo']->isPublic());
    }

    public function testNonArrayTagThrowsException()
    {
        $loader = new YamlFileLoader($container = new ContainerBuilder());
        try {
            $loader->load(__DIR__.'/Fixtures/yaml/badtag1.yml');
            $this->fail('->load() should throw an exception when the tags key of a service is not an array');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\InvalidArgumentException', $e, '->load() throws an InvalidArgumentException if the tags key is not an array');
            $this->assertStringStartsWith('Parameter "tags" must be an array for service', $e->getMessage(), '->load() throws an InvalidArgumentException if the tags key is not an array');
        }
    }

    public function testTagWithoutNameThrowsException()
    {
        $loader = new YamlFileLoader($container = new ContainerBuilder());
        try {
            $loader->load(__DIR__.'/Fixtures/yaml/badtag2.yml');
            $this->fail('->load() should throw an exception when a tag is missing the name key');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\InvalidArgumentException', $e, '->load() throws an InvalidArgumentException if a tag is missing the name key');
            $this->assertStringStartsWith('A "tags" entry is missing a "name" key for service ', $e->getMessage(), '->load() throws an InvalidArgumentException if a tag is missing the name key');
        }
    }

    public function testTagWithAttributeArrayThrowsException()
    {
        $loader = new YamlFileLoader($container = new ContainerBuilder());
        try {
            $loader->load(__DIR__.'/Fixtures/yaml/badtag3.yml');
            $this->fail('->load() should throw an exception when a tag-attribute is not a scalar');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\InvalidArgumentException', $e, '->load() throws an InvalidArgumentException if a tag-attribute is not a scalar');
            $this->assertStringStartsWith('A "tags" attribute must be of a scalar-type for service "foo_service", tag "foo", attribute "bar"', $e->getMessage(), '->load() throws an InvalidArgumentException if a tag-attribute is not a scalar');
        }
    }
}
