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
 * Model for the view rendering
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
     * @var string|int
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
     * Additional data, we are sending to the FE vas a json, hence the name.
     *
     * Right now, only the smokygrey skin makes use of this.
     *
     * @var array
     */
    protected $json = array();

    /**
     * A unique ID for the dom. We use this one for recursion resolving via JS.
     *
     * @var string
     */
    protected $domid = '';

    /**
     * Parameters for the renderMe method.
     *
     * Should be used in the extending classes.
     *
     * @var array
     */
    protected $parameters = array();

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
     * @param mixed $data
     *   The current variable we are rendering.
     * @param bool $escapeMe
     *   Sets it the data must be escaped.
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setData(&$data, $escapeMe = true)
    {
        if (is_string($data) && $escapeMe) {
            $this->data = Toolbox::encodeString($data);
        } else {
            $this->data = $data;
        }
        return $this;
    }

    /**
     * Getter for the data.
     *
     * @return mixed
     *   The variable, we are currently analysing.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Setter for the name.
     *
     * @param int|string $name
     *   The name/key we are analysing.
     * @param bool $escapeMe
     *   Sets if the name must be escaped.
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setName($name, $escapeMe = true)
    {
        if (is_string($name) && $escapeMe) {
            $this->name = Toolbox::encodeString($name);
        } else {
            $this->name = $name;
        }
        return $this;
    }

    /**
     * Getter for the name.
     *
     * @return int|string
     *   The name/key we are analysing.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Setter for normal.
     *
     * @param string $normal
     *   The short result of the analysis.
     * @param bool $escapeMe
     *   Sets if the short result must be escaped.
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setNormal($normal, $escapeMe = true)
    {
        if ($escapeMe) {
            $this->normal = Toolbox::encodeString($normal);
        } else {
            $this->normal = $normal;
        }
        return $this;
    }

    /**
     * Getter for normal.
     *
     * @return string
     *   The short result of the analysis.
     */
    public function getNormal()
    {
        return $this->normal;
    }

    /**
     * Setter for additional.
     *
     * @param string $additional
     *   The long result of the analysis.
     * @param bool $escapeMe
     *   Sets if the long result must be escaped.
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setAdditional($additional, $escapeMe = true)
    {
        if ($escapeMe) {
            $this->additional = Toolbox::encodeString($additional);
        } else {
            $this->additional = $additional;
        }

        return $this;
    }

    /**
     * Getter for additional
     *
     * @return mixed
     *   The long result of the analysis.
     */
    public function getAdditional()
    {
        return $this->additional;
    }

    /**
     * Setter for the type.
     *
     * @param string $type
     *   The type of the variable we are analysing.
     * @param bool $escapeMe
     *   Sets if the type of the variable must be escaped.
     *
     * @return \Brainworxx\Krexx\Model\Simple
     *   $this, for chaining.
     */
    public function setType($type, $escapeMe = true)
    {
        if ($escapeMe) {
            $this->type = Toolbox::encodeString($type);
        } else {
            $this->type = $type;
        }

        return $this;
    }

    /**
     * Getter for the type.
     *
     * @return string
     *   The type of the variable we are analysing
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Setter for the helpid.
     *
     * @param int $helpid
     *   The ID of the help text.
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
     * @return int
     *   The ID of the help text.
     */
    public function getHelpid()
    {
        return $this->helpid;
    }

    /**
     * Setter for connector1.
     *
     * @param string $connector1
     *   The first connector.
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
     *   The first connector.
     */
    public function getConnector1()
    {
        return $this->connector1;
    }

    /**
     * Setter for connector2.
     *
     * @param string $connector2
     *   The second connector.
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
     *   The second connector.
     */
    public function getConnector2()
    {
        return $this->connector2;
    }

    /**
     * Setter for json.
     *
     * @param array $json
     *   More analysis data.
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
     *   More analysis data.
     */
    public function getJson()
    {
        return $this->json;
    }

    /**
     * Setter for domid.
     *
     * @param string $domid
     *   The dom id, of cause.
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
     *   The dom id, of cause.
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
