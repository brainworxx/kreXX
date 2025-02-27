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

namespace Brainworxx\Krexx\Tests\Unit\Service\Config;

use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Model::class, 'setValue')]
#[CoversMethod(Model::class, 'setEditable')]
#[CoversMethod(Model::class, 'isEditable')]
#[CoversMethod(Model::class, 'setType')]
#[CoversMethod(Model::class, 'getSection')]
#[CoversMethod(Model::class, 'getType')]
#[CoversMethod(Model::class, 'getValue')]
#[CoversMethod(Model::class, 'setSection')]
#[CoversMethod(Model::class, 'getSource')]
#[CoversMethod(Model::class, 'setSource')]
class ModelTest extends AbstractHelper
{
    public const  VALUE = 'some value';

    /**
     * @var \Brainworxx\Krexx\Service\Config\Model
     */
    protected $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new Model();
    }

    public function testSetEditable()
    {
        $this->assertSame($this->model, $this->model->setEditable(true));
        $this->assertEquals(true, $this->model->isEditable());
    }

    public function testSetType()
    {
        $this->assertSame($this->model, $this->model->setType(static::VALUE));
        $this->assertEquals(static::VALUE, $this->model->getType());
    }

    /**
     * Testing the setting and the traqnformation into a boolean, if neccessary.
     */
    public function testSetValue()
    {
        $this->assertSame($this->model, $this->model->setValue(static::VALUE));
        $this->assertEquals(static::VALUE, $this->model->getValue());

        $this->assertSame($this->model, $this->model->setValue(Fallback::VALUE_FALSE));
        $this->assertEquals(false, $this->model->getValue());

        $this->assertSame($this->model, $this->model->setValue(Fallback::VALUE_TRUE));
        $this->assertEquals(true, $this->model->getValue());
    }

    public function testGetEditable()
    {
        $this->setValueByReflection('editable', true, $this->model);
        $this->assertEquals(true, $this->model->isEditable());
    }

    public function testGetSection()
    {
        $this->setValueByReflection('section', static::VALUE, $this->model);
        $this->assertEquals(static::VALUE, $this->model->getSection());
    }

    public function testGetType()
    {
        $this->setValueByReflection('type', static::VALUE, $this->model);
        $this->assertEquals(static::VALUE, $this->model->getType());
    }

    public function testGetValue()
    {
        $this->setValueByReflection('value', static::VALUE, $this->model);
        $this->assertEquals(static::VALUE, $this->model->getValue());
    }

    public function testSetSection()
    {
        $this->assertSame($this->model, $this->model->setSection(static::VALUE));
        $this->assertEquals(static::VALUE, $this->model->getSection());
    }

    public function testGetSource()
    {
        $this->setValueByReflection('source', static::VALUE, $this->model);
        $this->assertEquals(static::VALUE, $this->model->getSource());
    }

    public function testSetSource()
    {
        $this->assertSame($this->model, $this->model->setSource(static::VALUE));
        $this->assertEquals(static::VALUE, $this->model->getSource());
    }
}
