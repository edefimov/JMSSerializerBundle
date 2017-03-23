<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SerializerBundle\Tests\DependencyInjection;

use JMS\SerializerBundle\DependencyInjection\Configuration;
use JMS\SerializerBundle\JMSSerializerBundle;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    private function getContainer(array $configs = array())
    {
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir() . '/serializer');
        $container->setParameter('kernel.bundles', array('JMSSerializerBundle' => 'JMS\SerializerBundle\JMSSerializerBundle'));

        $bundle = new JMSSerializerBundle();

        $extension = $bundle->getContainerExtension();
        $extension->load($configs, $container);

        return $container;
    }

    public function testConfig()
    {
        $ref = new JMSSerializerBundle();
        $container = $this->getContainer([
            [
                'metadata' => [
                    'directories' => [
                        [
                            'namespace_prefix' => 'JMSSerializerBundleNs1',
                            'path' => '@JMSSerializerBundle',
                        ],
                        [
                            'namespace_prefix' => 'JMSSerializerBundleNs2',
                            'path' => '@JMSSerializerBundle/Resources/config',
                        ],
                    ]
                ]
            ],
        ]);

        $directories = $container->getDefinition('jms_serializer.metadata.file_locator')->getArgument(0);

        $this->assertEquals($ref->getPath(), $directories['JMSSerializerBundleNs1']);
        $this->assertEquals($ref->getPath().'/Resources/config', $directories['JMSSerializerBundleNs2']);
    }

    public function testContextDefaults()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(true), []);

        $this->assertArrayHasKey('default_context', $config);
        foreach (['serialization', 'deserialization'] as $item) {
            $this->assertArrayHasKey($item, $config['default_context']);

            $defaultContext = $config['default_context'][$item];

            $this->assertTrue(is_array($defaultContext['attributes']));
            $this->assertEmpty($defaultContext['attributes']);

            $this->assertTrue(is_array($defaultContext['groups']));
            $this->assertEmpty($defaultContext['groups']);

            $this->assertArrayNotHasKey('version', $defaultContext);
            $this->assertArrayNotHasKey('serialize_null', $defaultContext);
        }
    }

    public function testContextValues()
    {
        $configArray = array(
            'serialization' => array(
                'version' => 3,
                'serialize_null' => true,
                'attributes' => ['foo' => 'bar'],
                'groups' => ['Baz'],
            ),
            'deserialization' => array(
                'version' => "5.5",
                'serialize_null' => false,
                'attributes' => ['foo' => 'bar'],
                'groups' => ['Baz'],
            )
        );

        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(true), [
            'jms_serializer' => [
                'default_context' => $configArray
            ]
        ]);

        $this->assertArrayHasKey('default_context', $config);
        foreach (['serialization', 'deserialization'] as $configKey) {
            $this->assertArrayHasKey($configKey, $config['default_context']);

            $values = $config['default_context'][$configKey];
            $confArray = $configArray[$configKey];

            $this->assertSame($values['version'], $confArray['version']);
            $this->assertSame($values['serialize_null'], $confArray['serialize_null']);
            $this->assertSame($values['attributes'], $confArray['attributes']);
            $this->assertSame($values['groups'], $confArray['groups']);
        }
    }

    public function testContextNullValues()
    {
        $configArray = array(
            'serialization' => array(
                'version' => null,
                'serialize_null' => null,
                'attributes' => null,
                'groups' => null,
            ),
            'deserialization' => array(
                'version' => null,
                'serialize_null' => null,
                'attributes' => null,
                'groups' => null,
            )
        );

        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(true), [
            'jms_serializer' => [
                'default_context' => $configArray
            ]
        ]);

        $this->assertArrayHasKey('default_context', $config);
        foreach (['serialization', 'deserialization'] as $configKey) {
            $this->assertArrayHasKey($configKey, $config['default_context']);

            $defaultContext = $config['default_context'][$configKey];

            $this->assertTrue(is_array($defaultContext['attributes']));
            $this->assertEmpty($defaultContext['attributes']);

            $this->assertTrue(is_array($defaultContext['groups']));
            $this->assertEmpty($defaultContext['groups']);

            $this->assertArrayNotHasKey('version', $defaultContext);
            $this->assertArrayNotHasKey('serialize_null', $defaultContext);
        }
    }
}
