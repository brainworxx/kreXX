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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\View;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Misc\File;

/**
 * Render methods.
 *
 * It get extended by the render class of the used skin, so every skin can do
 * some special stuff.
 *
 * @package Brainworxx\Krexx\Service\View
 */
class Render extends AbstractRender
{

    /**
     * {@inheritdoc}
     */
    public function renderSingleChild(Model $model)
    {
        // This one is a little bit more complicated than the others,
        // because it assembles some partials and stitches them together.
        $template = $this->getTemplateFileContent('singleChild');
        $partExpand = '';
        $partCallable = '';
        $partExtra = '';
        $data = $model->getData();
        $extra = $model->getHasExtras();

        if ($extra) {
            // We have a lot of text, so we render this one expandable (yellow box).
            $partExpand = $this->getTemplateFileContent('singleChildExpand');
        }
        if ($model->getIsCallback()) {
            // Add callable partial.
            $partCallable = $this->getTemplateFileContent('singleChildCallable');
        }
        if ($extra) {
            // Add the yellow box for large output text.
            $partExtra = $this->getTemplateFileContent('singleChildExtra');
        }
        // Stitching the classes together, depending on the types.
        $typeArray = explode(' ', $model->getType());
        $typeClasses = '';
        foreach ($typeArray as $typeClass) {
            $typeClass = 'k' . $typeClass;
            $typeClasses .= $typeClass . ' ';
        }

        // Generating our code and adding the Codegen button, if there is something
        // to generate.
        $gensource = $this->pool->codegenHandler->generateSource($model);
        if (empty($gensource)) {
            // Remove the markers, because here is nothing to add.
            $template = str_replace('{gensource}', '', $template);
            $template = str_replace('{sourcebutton}', '', $template);
        } else {
            // We add the buttton and the code.
            $template = str_replace('{gensource}', $gensource, $template);
            $template = str_replace('{sourcebutton}', $this->getTemplateFileContent('sourcebutton'), $template);
        }

        // Stitching it together.
        $template = str_replace('{expand}', $partExpand, $template);
        $template = str_replace('{callable}', $partCallable, $template);
        $template = str_replace('{extra}', $partExtra, $template);
        $template = str_replace('{name}', $model->getName(), $template);
        $template = str_replace('{type}', $model->getType(), $template);
        $template = str_replace('{type-classes}', $typeClasses, $template);
        $template = str_replace('{normal}', $model->getNormal(), $template);
        $template = str_replace('{data}', $data, $template);
        $template = str_replace('{help}', $this->renderHelp($model), $template);
        $template = str_replace('{connector1}', $this->renderConnector($model->getConnector1()), $template);
        $template = str_replace('{gensource}', $gensource, $template);
        return str_replace('{connector2}', $this->renderConnector($model->getConnector2()), $template);
    }

    /**
     * {@inheritdoc}
     */
    public function renderRecursion(Model $model)
    {
        $template = $this->getTemplateFileContent('recursion');

        // Generating our code and adding the Codegen button, if there is
        // something to generate.
        $gencode = $this->pool->codegenHandler->generateSource($model);

        if (empty($gencode)) {
            // Remove the markers, because here is nothing to add.
            $template = str_replace('{gensource}', '', $template);
            $template = str_replace('{sourcebutton}', '', $template);
        } else {
            // We add the buttton and the code.
            $template = str_replace('{gensource}', $gencode, $template);
        }

        // Replace our stuff in the partial.
        $template = str_replace('{name}', $model->getName(), $template);
        $template = str_replace('{domId}', $model->getDomid(), $template);
        $template = str_replace('{normal}', $model->getNormal(), $template);
        $template = str_replace('{connector1}', $this->renderConnector($model->getConnector1()), $template);
        $template = str_replace('{help}', $this->renderHelp($model), $template);

        return str_replace('{connector2}', $this->renderConnector($model->getConnector2()), $template);
    }

    /**
     * {@inheritdoc}
     */
    public function renderHeader($doctype, $headline, $cssJs)
    {
        $template = $this->getTemplateFileContent('header');
        // Replace our stuff in the partial.
        $template = str_replace('{version}', $this->pool->config->version, $template);
        $template = str_replace('{doctype}', $doctype, $template);
        $template = str_replace('{KrexxCount}', $this->pool->emergencyHandler->getKrexxCount(), $template);
        $template = str_replace('{headline}', $headline, $template);
        $template = str_replace('{cssJs}', $cssJs, $template);
        $template = str_replace('{KrexxId}', $this->pool->recursionHandler->getMarker(), $template);
        $template = str_replace('{search}', $this->renderSearch(), $template);
        $template = str_replace('{messages}', $this->pool->messages->outputMessages(), $template);

        return $template;
    }

    /**
     * {@inheritdoc}
     */
    public function renderFooter($caller, $configOutput, $configOnly = false)
    {
        $template = $this->getTemplateFileContent('footer');
        // Replace our stuff in the partial.
        if (!isset($caller['file'])) {
            // When we have no caller, we will not render it.
            $template = str_replace('{caller}', '', $template);
        } else {
            $template = str_replace('{caller}', $this->renderCaller($caller['file'], $caller['line']), $template);
        }
        $template = str_replace('{configInfo}', $configOutput, $template);
        return $template;
    }

    /**
     * {@inheritdoc}
     */
    public function renderCssJs($css, $js)
    {
        $template = $this->getTemplateFileContent('cssJs');
        // Replace our stuff in the partial.
        $template = str_replace('{css}', $css, $template);
        $template = str_replace('{js}', $js, $template);
        return $template;
    }

    /**
     * {@inheritdoc}
     */
    public function renderExpandableChild(Model $model, $isExpanded = false)
    {
        // Check for emergency break.
        if (!$this->pool->emergencyHandler->checkEmergencyBreak()) {
            return '';
        }

        // We need to render this one normally.
        $template = $this->getTemplateFileContent('expandableChildNormal');
        // Replace our stuff in the partial.
        $template = str_replace('{name}', $model->getName(), $template);
        $template = str_replace('{type}', $model->getType(), $template);

        // Explode the type to get the class names right.
        $types = explode(' ', $model->getType());
        $cssType = '';
        foreach ($types as $singleType) {
            $cssType .= ' k' . $singleType;
        }
        $template = str_replace('{ktype}', $cssType, $template);

        $template = str_replace('{normal}', $model->getNormal(), $template);
        $template = str_replace('{help}', $this->renderHelp($model), $template);
        $template = str_replace('{connector1}', $this->renderConnector($model->getConnector1()), $template);
        $template = str_replace('{connector2}', $this->renderConnector($model->getConnector2()), $template);

        // Generating our code and adding the Codegen button, if there is
        // something to generate.
        $gencode = $this->pool->codegenHandler->generateSource($model);
        $template = str_replace('{gensource}', $gencode, $template);
        if ($gencode === ';stop;' || empty($gencode)) {
            // Remove the button marker, because here is nothing to add.
            $template = str_replace('{sourcebutton}', '', $template);
        } else {
            // Add the button.
            $template = str_replace('{sourcebutton}', $this->getTemplateFileContent('sourcebutton'), $template);
        }

        // Is it expanded?
        if ($isExpanded) {
            $template = str_replace('{isExpanded}', 'kopened', $template);
        } else {
            $template = str_replace('{isExpanded}', '', $template);
        }
        return str_replace(
            '{nest}',
            $this->pool->chunks->chunkMe($this->renderNest($model, $isExpanded)),
            $template
        );

    }

    /**
     * {@inheritdoc}
     */
    public function renderSingleEditableChild(Model $model)
    {
        $template = $this->getTemplateFileContent('singleEditableChild');
        $element = $this->getTemplateFileContent('single' . $model->getType());

        $element = str_replace('{name}', $model->getData(), $element);
        $element = str_replace('{value}', $model->getName(), $element);

        // For dropdown elements, we need to render the options.
        if ($model->getType() === 'Select') {
            $option = $this->getTemplateFileContent('single' . $model->getType() . 'Options');

            // Here we store what the list of possible values.
            switch ($model->getData()) {
                case "destination":
                    // At php shutdown, logfile or direct after analysis.
                    $valueList = array('browser', 'file');
                    break;

                case "skin":
                    // Get a list of all skin folders.
                    $valueList = $this->getSkinList();
                    break;

                default:
                    // true/false
                    $valueList = array('true', 'false');
                    break;
            }

            // Paint it.
            $options = '';
            foreach ($valueList as $value) {
                if ($value === $model->getName()) {
                    // This one is selected.
                    $selected = 'selected="selected"';
                } else {
                    $selected = '';
                }
                $options .= str_replace(array(
                    '{text}',
                    '{value}',
                    '{selected}',
                ), array(
                    $value,
                    $value,
                    $selected,
                ), $option);
            }
            // Now we replace the options in the output.
            $element = str_replace('{options}', $options, $element);
        }

        $template = str_replace('{name}', $model->getData(), $template);
        $template = str_replace('{source}', $model->getNormal(), $template);
        $template = str_replace('{normal}', $element, $template);
        $template = str_replace('{type}', 'editable', $template);
        $template = str_replace('{help}', $this->renderHelp($model), $template);

        return $template;
    }

    /**
     * {@inheritdoc}
     */
    public function renderButton(Model $model)
    {
        $template = $this->getTemplateFileContent('singleButton');
        $template = str_replace('{help}', $this->renderHelp($model), $template);

        $template = str_replace('{text}', $model->getNormal(), $template);
        return str_replace('{class}', $model->getName(), $template);
    }

    /**
     * {@inheritdoc}
     */
    public function renderFatalMain($type, $errstr, $errfile, $errline)
    {
        $template = $this->getTemplateFileContent('fatalMain');

        $from = $errline -5;
        $to = $errline +5;
        $source = $this->fileService->readSourcecode($errfile, $errline -1, $from -1, $to -1);

        // Insert our values.
        $template = str_replace('{type}', $type, $template);
        $template = str_replace('{errstr}', $errstr, $template);
        $template = str_replace('{file}', $errfile, $template);
        $template = str_replace('{source}', $source, $template);
        $template = str_replace('{KrexxCount}', $this->pool->emergencyHandler->getKrexxCount(), $template);

        return str_replace('{line}', $errline, $template);
    }

    /**
     * {@inheritdoc}
     */
    public function renderFatalHeader($cssJs, $doctype)
    {
        $template = $this->getTemplateFileContent('fatalHeader');

        // Insert our values.
        $template = str_replace('{cssJs}', $cssJs, $template);
        $template = str_replace('{version}', $this->pool->config->version, $template);
        $template = str_replace('{doctype}', $doctype, $template);
        $template = str_replace('{search}', $this->renderSearch(), $template);

        return str_replace('{KrexxId}', $this->pool->recursionHandler->getMarker(), $template);
    }

    /**
     * {@inheritdoc}
     */
    public function renderMessages(array $messages)
    {
        $template = $this->getTemplateFileContent('message');
        $result = '';

        foreach ($messages as $message) {
            $temp = str_replace('{class}', $message['class'], $template);
            $result .= str_replace('{message}', $message['message'], $temp);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function renderBacktraceSourceLine($className, $lineNo, $sourceCode)
    {
        $template = $this->getTemplateFileContent('backtraceSourceLine');
        $template = str_replace('{className}', $className, $template);
        $template = str_replace('{lineNo}', $lineNo, $template);

        return str_replace('{sourceCode}', $sourceCode, $template);
    }

    /**
     * {@inheritdoc}
     */
    public function renderSingeChildHr()
    {
        return $this->getTemplateFileContent('singleChildHr');
    }
}
