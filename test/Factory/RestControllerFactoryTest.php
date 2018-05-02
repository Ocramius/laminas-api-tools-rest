<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2017 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Rest\Factory;

use ZF\Rest\Factory\RestControllerFactory;
use PHPUnit\Framework\TestCase;
use Zend\Mvc\Controller\ControllerManager;
use Zend\ServiceManager\ServiceManager;

class RestControllerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services    = $services    = new ServiceManager();
        $this->controllers = $controllers = new ControllerManager($this->services);
        $this->factory     = $factory     = new RestControllerFactory();

        $controllers->addAbstractFactory($factory);

        $services->setService('Zend\ServiceManager\ServiceLocatorInterface', $services);
        $services->setService('config', $this->getConfig());
        $services->setService('ControllerManager', $controllers);
        $services->setFactory('ControllerPluginManager', 'Zend\Mvc\Service\ControllerPluginManagerFactory');
        $services->setInvokableClass('EventManager', 'Zend\EventManager\EventManager');
        $services->setInvokableClass('SharedEventManager', 'Zend\EventManager\SharedEventManager');
        $services->setShared('EventManager', false);
    }

    public function getConfig()
    {
        return [
            'zf-rest' => [
                'ApiController' => [
                    'listener'   => 'ZFTest\Rest\Factory\TestAsset\Listener',
                    'route_name' => 'api',
                ],
            ],
        ];
    }

    public function testWillInstantiateListenerIfServiceNotFoundButClassExists()
    {
        $this->assertTrue($this->controllers->has('ApiController'));
        $controller = $this->controllers->get('ApiController');
        $this->assertInstanceOf('ZF\Rest\RestController', $controller);
    }

    public function testWillInstantiateAlternateRestControllerWhenSpecified()
    {
        $config = $this->services->get('config');
        $config['zf-rest']['ApiController']['controller_class'] = 'ZFTest\Rest\Factory\TestAsset\CustomController';
        $this->services->setAllowOverride(true);
        $this->services->setService('config', $config);
        $controller = $this->controllers->get('ApiController');
        $this->assertInstanceOf('ZFTest\Rest\Factory\TestAsset\CustomController', $controller);
    }

    public function testDefaultControllerEventManagerIdentifiersAreAsExpected()
    {
        $controller = $this->controllers->get('ApiController');
        $events = $controller->getEventManager();

        $identifiers = $events->getIdentifiers();

        $this->assertContains('ZF\Rest\RestController', $identifiers);
        $this->assertContains('ApiController', $identifiers);
    }

    public function testControllerEventManagerIdentifiersAreAsSpecified()
    {
        $config = $this->services->get('config');
        $config['zf-rest']['ApiController']['identifier'] = 'ZFTest\Rest\Factory\TestAsset\ExtraControllerListener';
        $this->services->setAllowOverride(true);
        $this->services->setService('config', $config);

        $controller = $this->controllers->get('ApiController');
        $events = $controller->getEventManager();

        $identifiers = $events->getIdentifiers();

        $this->assertContains('ZF\Rest\RestController', $identifiers);
        $this->assertContains('ZFTest\Rest\Factory\TestAsset\ExtraControllerListener', $identifiers);
    }

    public function testDefaultResourceEventManagerIdentifiersAreAsExpected()
    {
        $controller = $this->controllers->get('ApiController');
        $resource = $controller->getResource();
        $events = $resource->getEventManager();

        $expected = [
            'ZFTest\Rest\Factory\TestAsset\Listener',
            'ZF\Rest\Resource',
            'ZF\Rest\ResourceInterface',
        ];
        $identifiers = $events->getIdentifiers();

        $this->assertEquals(array_values($expected), array_values($identifiers));
    }

    public function testResourceEventManagerIdentifiersAreAsSpecifiedString()
    {
        $config = $this->services->get('config');
        $config['zf-rest']['ApiController']['resource_identifiers'] =
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener';
        $this->services->setAllowOverride(true);
        $this->services->setService('config', $config);

        $controller = $this->controllers->get('ApiController');
        $resource = $controller->getResource();
        $events = $resource->getEventManager();

        $expected = [
            'ZFTest\Rest\Factory\TestAsset\Listener',
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener',
            'ZF\Rest\Resource',
            'ZF\Rest\ResourceInterface',
        ];
        $identifiers = $events->getIdentifiers();

        $this->assertEquals(array_values($expected), array_values($identifiers));
    }

    public function testResourceEventManagerIdentifiersAreAsSpecifiedArray()
    {
        $config = $this->services->get('config');
        $config['zf-rest']['ApiController']['resource_identifiers'] = [
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener1',
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener2',
        ];
        $this->services->setAllowOverride(true);
        $this->services->setService('config', $config);

        $controller = $this->controllers->get('ApiController');
        $resource = $controller->getResource();
        $events = $resource->getEventManager();

        $expected = [
            'ZFTest\Rest\Factory\TestAsset\Listener',
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener1',
            'ZFTest\Rest\Factory\TestAsset\ExtraResourceListener2',
            'ZF\Rest\Resource',
            'ZF\Rest\ResourceInterface',
        ];
        $identifiers = $events->getIdentifiers();

        $this->assertEquals(array_values($expected), array_values($identifiers));
    }
}
