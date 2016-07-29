<?php
/**
 * @file
 *   Model for the view rendering
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Model;

use Brainworxx\Krexx\Framework\Toolbox;

/**
 * Here we host the basic getter / setter for all the info we are giving to
 * the render class. All in all, just a bunch of setter/getter.
 *
 * @package Brainworxx\Krexx\Model
 */
class Simple
{
    /**
     * The object/string/arraqy/whatever we are analysing right now
     *
     * @var mixed
     */
    protected $data;

    /**
     * The name/key of it.
     *
     * @var string
     */
    protected $name = '';

    /**
     * The short result of the analysis.
     *
     * @var string
     */
    protected $normal = '';

    /**
     * When the long result of the analysis, used if "normal" does not
     * provide enough room.
     *
     * @var string
     */
    protected $additional = '';

    /**
     * The type of the variable we are analysing, in a string.
     *
     * @var string
     */
    protected $type = '';

    /**
     * The ID of the help text.
     *
     * @var string
     */
    protected $helpid = '';

    /**
     * The first connector.
     *
     * @var string
     */
    protected $connector1 = '';

    /**
     * The second connector.
     *
     * @var string
     */
    protected $connector2 = '';

    /**
     * Additinal data, we are sending to the FE vas a json, hence the name.
     * Right now, only the Smoky-Grey skin makes use of this.
     *
     * @var array
     */
    protected $json = array();

    /**
     * A unique ID for the dom. We use this one for recursion resolving
     * via JS.
     *
     * @var string
     */
    protected $domid = '';

    /**
     * Parameters for the renderMe method. Should be used in the
     * extending classes.
     *
     * @var array
     */
    protected $parameters = array();

    /**
     * Is the containing $data already escaped at this time?
     *
     * @var bool
     */
    protected $isEscaped = false;

    /**
     * Setter for the $isEscaped.
     *
     * @param $isEscaped
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setIsEscaped($isEscaped)
    {
        $this->isEscaped = $isEscaped;
        return $this;
    }

    /**
     * Placeholder for the render function. Overwrite this one
     *
     * @return string
     */
    public function renderMe()
    {
        return '';
    }

    /**
     * Setter for the data.
     *
     * @param $data
     *   The current variable we are rendering.
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setData(&$data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Getter for the data.
     *
     * @return mixed
     */
    public function getData()
    {
        if (is_string($this->data) && !$this->isEscaped) {
            return Toolbox::encodeString($this->data);
        } else {
            return $this->data;
        }
    }

    /**
     * Setter for the name.
     *
     * @param string $name
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Getter for the name.
     *
     * @return string
     */
    public function getName()
    {
        if (is_string($this->name)) {
            return Toolbox::encodeString($this->name);
        } else {
            return $this->name;
        }
    }

    /**
     * Setter for normal.
     *
     * @param string $normal
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setNormal($normal)
    {
        $this->normal = $normal;
        return $this;
    }

    /**
     * Getter for normal.
     *
     * @return string
     */
    public function getNormal()
    {
        if (is_string($this->normal)) {
            return Toolbox::encodeString($this->normal);
        } else {
            return $this->normal;
        }
    }

    /**
     * Setter for additional.
     *
     * @param string $additional
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setAdditional($additional)
    {
        $this->additional = $additional;
        return $this;
    }

    /**
     * Getter for additional
     *
     * @return string
     */
    public function getAdditional()
    {
        if (is_string($this->additional)) {
            return Toolbox::encodeString($this->additional);
        } else {
            return $this->additional;
        }
    }

    /**
     * Setter for the type.
     *
     * @param string $type
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Getter for the type.
     *
     * @return string
     */
    public function getType()
    {
        if (is_string($this->type)) {
            return Toolbox::encodeString($this->type);
        } else {
            return $this->type;
        }
    }

    /**
     * Setter for the helpid.
     *
     * @param string $helpid
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setHelpid($helpid)
    {
        $this->helpid = $helpid;
        return $this;
    }

    /**
     * Getter for the help id.
     *
     * @return string
     */
    public function getHelpid()
    {
        return $this->helpid;
    }

    /**
     * Setter for connector1.
     *
     * @param string $connector1
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setConnector1($connector1)
    {
        $this->connector1 = $connector1;
        return $this;
    }

    /**
     * Getter got connector1.
     *
     * @return string
     */
    public function getConnector1()
    {
        return $this->connector1;
    }

    /**
     * Setter for connector2.
     *
     * @param string $connector2
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setConnector2($connector2)
    {
        $this->connector2 = $connector2;
        return $this;
    }

    /**
     * Getter for connector2.
     *
     * @return string
     */
    public function getConnector2()
    {
        return $this->connector2;
    }

    /**
     * Setter for json.
     *
     * @param array $json
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setJson($json)
    {
        $this->json = $json;
        return $this;
    }

    /**
     * Getter for json.
     *
     * @return array
     */
    public function getJson()
    {
        return $this->json;
    }

    /**
     * Setter for domid.
     *
     * @param string $domid
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setDomid($domid)
    {
        $this->domid = $domid;
        return $this;
    }

    /**
     * Getter for domid.
     *
     * @return string
     */
    public function getDomid()
    {
        return $this->domid;
    }

    /**
     * Simply add a parameter for the $closure.
     *
     * @param $name
     *   The name of the parameter.
     * @param $value
     *   The value of the parameter, by referance.
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function addParameter($name, &$value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }
}
