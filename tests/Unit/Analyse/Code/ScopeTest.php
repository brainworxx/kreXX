<?php

/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Code;

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Code\Scope;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Fixtures\PublicFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Krexx;
use stdClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Scope::class, '__construct')]
#[CoversMethod(Scope::class, 'setScope')]
#[CoversMethod(Scope::class, 'getScope')]
#[CoversMethod(Scope::class, 'testModelForCodegen')]
#[CoversMethod(Scope::class, 'isInScope')]
class ScopeTest extends AbstractHelper
{
    public const  SCOPE_ATTRIBUTE_NAME = 'scope';
    public const  TEST_STRING = 'some scope';

    /**
     * @var Scope
     */
    protected $scope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scope = new Scope(Krexx::$pool);
    }

    /**
     * Testing the pool handling.
     */
    public function testConstruct()
    {
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->scope));
        $this->assertEquals($this->scope, Krexx::$pool->scope);
    }

    /**
     * Test the setting of the scope
     */
    public function testSetScope()
    {
        $codegenMock = $this->createMock(Codegen::class);
        $codegenMock->expects($this->never())
            ->method('setCodegenAllowed');
        Krexx::$pool->codegenHandler = $codegenMock;

        $this->scope->setScope($this->scope::UNKNOWN_VALUE);
        $this->assertEquals('', $this->scope->getScope());

        $codegenMock = $this->createMock(Codegen::class);
        $codegenMock->expects($this->once())
            ->method('setCodegenAllowed')
            ->with(true);
        Krexx::$pool->codegenHandler = $codegenMock;

        $this->scope->setScope(static::TEST_STRING);
        $this->assertEquals(static::TEST_STRING, $this->scope->getScope());
    }

    /**
     * Test the scope getting
     */
    public function testGetScope()
    {
        $this->setValueByReflection(static::SCOPE_ATTRIBUTE_NAME, static::TEST_STRING, $this->scope);
        $this->assertEquals(static::TEST_STRING, $this->scope->getScope());
    }

    /**
     * Test the test the model for gode generation.
     *
     * Next time on this channel:
     * We test the tests of the test of the tests.
     * Are there unit tests for unit test?
     * Is this the ultimate test for our not-so-young hero?
     */
    public function testTestModelForCodegen()
    {
        // Some fixtures
        $object = new stdClass();
        $array = [];
        $string = 'whatever';
        $objectWithPrivate = new PublicFixture();

        // No generation for 'some' scope.
        $this->setNestingLevel(1);
        $this->scope->setScope('some');
        $model = new Model(Krexx::$pool);
        $this->assertFalse($this->scope->testModelForCodegen($model));

        // No generation for a deep nesting level.
        $this->setNestingLevel(5);
        $this->scope->setScope('$this');
        $this->assertFalse($this->scope->testModelForCodegen($model));

        // No generation for private and inherited property.
        $this->setNestingLevel(2);
        $model->setType('private inherited');
        $this->assertFalse($this->scope->testModelForCodegen($model));
        $model->setType('');

        // Code generation for a level 2 object.
        $this->setNestingLevel(2);
        $model->setData($object);
        $this->assertTrue($this->scope->testModelForCodegen($model));

        // Code generation with a real class on level 1
        $messages = Krexx::$pool->messages;
        $this->setNestingLevel(1);
        $model->setData($objectWithPrivate)
            ->setType(
                $messages->getHelp('private') . ' ' . $messages->getHelp('inherited')
            );
        $this->scope->setScope('$this');
        $this->assertFalse($this->scope->testModelForCodegen($model));
        $model->setType('');

        // Code generation for a level 2 array.
        $this->setNestingLevel(2);
        $model->setData($array);
        $this->assertTrue($this->scope->testModelForCodegen($model));

        // No generation for a level 2 string.
        $this->setNestingLevel(2);
        $model->setData($string);
        $this->assertFalse($this->scope->testModelForCodegen($model));

        // Code generation for a level 1 string.
        $this->setNestingLevel(1);
        $model->setData($string);
        $this->assertTrue($this->scope->testModelForCodegen($model));
    }

    /**
     * Testing the Scope "analysis".
     */
    public function testIsInScope()
    {
        $this->setNestingLevel(1);

        $this->scope->setScope('$this');
        $this->assertTrue($this->scope->isInScope());

        $this->scope->setScope('$that');
        $this->assertFalse($this->scope->isInScope());

        $this->setNestingLevel(5);
        $this->scope->setScope('$this');
        $this->assertFalse($this->scope->isInScope());
    }

    /**
     * Set the nesting level in the emergengcy handler moch
     *
     * @param int $level
     */
    protected function setNestingLevel($level)
    {
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('getNestingLevel')
            ->willReturn($level);
        Krexx::$pool->emergencyHandler = $emergencyMock;
    }
}
