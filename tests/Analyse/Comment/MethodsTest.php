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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Analyse\Comment;

use Brainworxx\Krexx\Analyse\Comment\Methods;
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Fixtures\InheritDocFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class MethodsTest extends AbstractTest
{
    /**
     * @var \Brainworxx\Krexx\Analyse\Comment\Methods
     */
    protected $methodComment;

    /**
     * {@inheritdoc}
     * Creating out class instance to test.
     */
    public function setUp()
    {
        parent::setUp();

        $this->methodComment = new Methods(\Krexx::$pool);
    }

    /**
     * @param string $className
     * @param string $methodName
     */
    protected function returnTestResult($className, $methodName)
    {
        $reflectionClass = new \ReflectionClass($className);
        return $this->methodComment->getComment($reflectionClass->getMethod($methodName), $reflectionClass);
    }

    /**
     * Test the comment retrieval of class methods.
     *
     * Comment in plain sight above method.
     *
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\AbstractComment::prettifyComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getInterfaceComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getTraitComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\AbstractComment::replaceInheritComment
     */
    public function testGetCommentNormal()
    {
        $this->assertEquals(
            'Static function',
            $this->returnTestResult(ComplexMethodFixture::class, 'staticMethod')
        );
    }

    /**
     * Test the comment retrieval of class methods.
     *
     * Comment inherited from underlying class
     *
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\AbstractComment::prettifyComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getInterfaceComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getTraitComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\AbstractComment::replaceInheritComment
     */
    public function testGetCommentInherited()
    {
        $this->assertEquals(
            '&#64;param $parameter',
            $this->returnTestResult(InheritDocFixture::class, 'parameterizedMethod')
        );
    }

    /**
     * Test the comment retrieval of class methods.
     *
     * Comment from an interface.
     *
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\AbstractComment::prettifyComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getInterfaceComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getTraitComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\AbstractComment::replaceInheritComment
     */
    public function testGetCommentInterface()
    {
        $this->markTestIncomplete('Write me!');
    }

    /**
     * Test the comment retrieval of class methods.
     *
     * Comment from a trait.
     *
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\AbstractComment::prettifyComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getInterfaceComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getTraitComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\AbstractComment::replaceInheritComment
     */
    public function testGetCommentTrait()
    {
        $this->markTestIncomplete('Write me!');
    }

    /**
     * Test the comment retrieval of class methods.
     *
     * Unresolvable comment.
     *
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\AbstractComment::prettifyComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getInterfaceComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\Methods::getTraitComment
     * @covers \Brainworxx\Krexx\Analyse\Comment\AbstractComment::replaceInheritComment
     */
    public function testGetCommentUnresolvable()
    {
        $this->markTestIncomplete('Write me!');
    }
}
