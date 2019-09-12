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

namespace Brainworxx\Krexx\View\Skins\Hans;

use Brainworxx\Krexx\Analyse\Model;

trait ExpandableChild
{
    /**
     * {@inheritdoc}
     */
    public function renderExpandableChild(Model $model, $isExpanded = false)
    {
        // Check for emergency break.
        if ($this->pool->emergencyHandler->checkEmergencyBreak() === true) {
            return '';
        }

        // Explode the type to get the class names right.
        $cssType = '';
        $modelType = $model->getType();
        foreach (explode(' ', $modelType) as $singleType) {
            $cssType .= ' k' . $singleType;
        }

        // Generating our code and adding the Codegen button, if there is
        // something to generate.
        $gencode = $this->pool->codegenHandler->generateSource($model);
        if ($gencode === ';stop;' ||
            empty($gencode) === true ||
            $this->pool->codegenHandler->getAllowCodegen() === false
        ) {
            // Remove the button marker, because here is nothing to add.
            $sourceButton = '';
        } else {
            // Add the button.
            $sourceButton = $this->getTemplateFileContent(static::FILE_SOURCE_BUTTON);
        }

        // Is it expanded?
        if ($isExpanded === true) {
            $expandedClass = 'kopened';
        } else {
            $expandedClass = '';
        }

        return str_replace(
            [
                static::MARKER_NAME,
                static::MARKER_TYPE,
                static::MARKER_K_TYPE,
                static::MARKER_NORMAL,
                static::MARKER_CONNECTOR_LEFT,
                static::MARKER_CONNECTOR_RIGHT,
                static::MARKER_GEN_SOURCE,
                static::MARKER_SOURCE_BUTTON,
                static::MARKER_IS_EXPANDED,
                static::MARKER_NEST,
                static::MARKER_CODE_WRAPPER_LEFT,
                static::MARKER_CODE_WRAPPER_RIGHT,
                static::MARKER_HELP,
            ],
            [
                $model->getName(),
                $modelType,
                $cssType,
                $model->getNormal(),
                $this->renderConnectorLeft($model->getConnectorLeft()),
                $this->renderConnectorRight($model->getConnectorRight(128)),
                $this->generateDataAttribute(static::DATA_ATTRIBUTE_SOURCE, $gencode),
                $sourceButton,
                $expandedClass,
                $this->pool->chunks->chunkMe($this->renderNest($model, $isExpanded)),
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_WRAPPER_L,
                    $this->pool->codegenHandler->generateWrapperLeft()
                ),
                $this->generateDataAttribute(
                    static::DATA_ATTRIBUTE_WRAPPER_R,
                    $this->pool->codegenHandler->generateWrapperRight()
                ),
                $this->renderHelp($model),
            ],
            $this->getTemplateFileContent(static::FILE_EX_CHILD_NORMAL)
        );
    }

    /**
     * Renders a nest with a anonymous function in the middle.
     *
     * @param Model $model
     *   The model, which hosts all the data we need.
     * @param bool $isExpanded
     *   The only expanded nest is the settings menu, when we render only the
     *   settings menu.
     *
     * @return string
     *   The generated markup from the template files.
     */
    protected function renderNest(Model $model, $isExpanded = false)
    {
        // Get the dom id.
        $domid = $model->getDomid();
        if ($domid !== '') {
            $domid = 'id="' . $domid . '"';
        }

        // Are we expanding this one?
        if ($isExpanded === true) {
            $style = '';
        } else {
            $style = static::STYLE_HIDDEN;
        }

        return str_replace(
            [
                static::MARKER_STYLE,
                static::MARKER_MAIN_FUNCTION,
                static::MARKER_DOM_ID,
            ],
            [
                $style,
                $model->renderMe(),
                $domid,
            ],
            $this->getTemplateFileContent(static::FILE_NEST)
        );
    }
}
