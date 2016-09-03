<?php
/**
 * Created by PhpStorm.
 * User: guelzow
 * Date: 03.09.2016
 * Time: 12:55
 */

namespace Brainworxx\Krexx\Service\Config;

class Setting
{
    /**
     * The value of this setting.
     *
     * @var string
     */
    protected $value;

    /**
     * The section of this setting.
     *
     * @var string
     */
    protected $section;

    /**
     * The type of this setting.
     *
     * @var string
     */
    protected $type;

    /**
     * Whether or not his setting is editable
     *
     * @var string
     */
    protected $editable;

    /**
     * Source of this setting.
     *
     * @var string
     */
    protected $source;

    /**
     * Setter for the editable value.
     *
     * @param string $editable
     *
     * @return $this
     *   Return $this for Chaining.
     */
    public function setEditable($editable)
    {
        $this->editable = $editable;
        return $this;
    }

    /**
     * Setter for the type.
     *
     * @param string $type
     *
     * @return $this
     *   Return $this for Chaining.
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Setter for the value.
     *
     * @param string $value
     *
     * @return $this
     *   Return $this for Chaining.
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Getter for the editable value.
     *
     * @return string
     */
    public function getEditable()
    {
        return $this->editable;
    }

    /**
     * Getter for the section.
     *
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Getter for the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Getter for the value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Setter for the section.
     *
     * @param string $section
     *
     * @return $this
     *   Return $this for Chaining.
     */
    public function setSection($section)
    {
        $this->section = $section;
        return $this;
    }

    /**
     * Getter for the source value.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Setter for the source value.
     *
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }
}
