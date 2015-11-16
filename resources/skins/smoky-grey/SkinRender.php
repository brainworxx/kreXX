<?php
/**
 * @file
 *   Render functions for kreXX Smokey-Grey Skin
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
 *   kreXX Copyright (C) 2014-2015 Brainworxx GmbH
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

namespace Brainworxx\Krexx\View;

use Brainworxx\Krexx\Analysis;
use Brainworxx\Krexx\Framework;

class SkinRender extends Render {


  /**
   * {@inheritDoc}
   */
  Public static function renderSingleChild($data, $name = '', $normal = '', $extra = FALSE, $type = '', $strlen = '', $help_id = '', $connector1 = '', $connector2 = '', $is_footer = FALSE) {

    $template = parent::renderSingleChild($data, $name, $normal, $extra, $type, $strlen, $help_id, $connector1, $connector2, $is_footer);

    // Prepare the json.
    $json = json_encode(array(
      'Help' => self::getHelp($help_id),
//      'Key' => $name,
//      'Type' => $type,
      'Length' => $strlen,
    ));
    $template = str_replace('{addjson}', $json, $template);

    return  $template;
  }


  /**
   * {@inheritDoc}
   */
  Public static function renderExpandableChild($name, $type, \Closure $anon_function, &$parameter, $additional = '', $dom_id = '', $help_id = '', $is_expanded = FALSE, $connector1 = '', $connector2 = '') {

    // Check for emergency break.
    if (!Analysis\Internals::checkEmergencyBreak()) {
      // Normally, this should not show up, because the Chunks class will not
      // output anything, except a JS alert.
      Messages::addMessage("Emergency break for large output during rendering process.\n\nYou should try to switch to file output.");
      return '';
    }


    if ($name == '' && $type == '') {
      // Without a Name or Type I only display the Child with a Node.
      $template = self::getTemplateFileContent('expandableChildSimple');
      // Replace our stuff in the partial.
      return str_replace('{mainfunction}', Framework\Chunks::chunkMe($anon_function($parameter)), $template);
    }
    else {
      // We need to render this one normally.
      $template = self::getTemplateFileContent('expandableChildNormal');
      // Replace our stuff in the partial.
      $template = str_replace('{name}', $name, $template);
      $template = str_replace('{type}', $type, $template);

      // Explode the type to get the class names right.
      $types = explode(' ', $type);
      $css_type = '';
      foreach ($types as $single_type) {
        $css_type .= ' k' . $single_type;
      }
      $template = str_replace('{ktype}', $css_type, $template);

      $template = str_replace('{additional}', $additional, $template);
      $template = str_replace('{help}', self::renderHelp($help_id), $template);
      $template = str_replace('{connector1}', self::renderConnector($connector1), $template);
      $template = str_replace('{connector2}', self::renderConnector($connector2), $template);

      // Generating our code and adding the Codegen button, if there is
      // something to generate.
      $gencode = Codegen::generateSource($connector1, $connector2, $type, $name);
      if ($gencode == '') {
        // Remove the markers, because here is nothing to add.
        $template = str_replace('{gensource}', '', $template);
        $template = str_replace('{gencode}', '', $template);
      }
      else {
        // We add the buttton and the code.
        $template = str_replace('{gensource}', $gencode, $template);
        $template = str_replace('{gencode}', self::getTemplateFileContent('gencode'), $template);
      }

      // Is it expanded?
      // This is done in the js.
      $template = str_replace('{isExpanded}', '', $template);

      // Prepare the json.
      if ($type != 'class') {
        // If we analyse the class, the classname is stored inside the
        // additional.
        $additional = '';
      }

      $json = json_encode(array(
        'Help' => self::getHelp($help_id),
//        'Key' => $name,
//        'Type' => $type,
        'Classname' => $additional,
      ));
      $template = str_replace('{addjson}', $json, $template);

      return str_replace('{nest}', Framework\Chunks::chunkMe(self::renderNest($anon_function, $parameter, $dom_id, FALSE)), $template);
    }
  }


  /**
   * {@inheritDoc}
   */
  Public static function renderSingleEditableChild($name, $normal, $source, $input_type, $help_id = '') {

    $template = parent::renderSingleEditableChild($name, $normal, $source, $input_type, $help_id);

    // Prepare the json. Not much do display for form elements.
    $json = json_encode(array(
      'Help' => self::getHelp($help_id)
    ));
    $template = str_replace('{addjson}', $json, $template);

    return $template;
  }

  /**
   * {@inheritDoc}
   */
  Public static function renderButton($name = '', $text = '', $help_id = '') {

    $template = parent::renderButton($name, $text, $help_id);

    // Prepare the json. Not much do display for form elements.
    $json = json_encode(array(
      'Help' => self::getHelp($help_id)
    ));
    $template = str_replace('{addjson}', $json, $template);

    return str_replace('{class}', $name, $template);
  }

  /**
   * {@inheritDoc}
   */
  Public static function renderHeader($doctype, $headline, $css_js) {
    $template = parent::renderHeader($doctype, $headline, $css_js);

    // Doing special stuff for smoky-grey:
    // We hide the debug-tab when we are displaying the config-only and switch
    // to the config as the current payload.
    if ($headline == 'Edit local settings') {
      $template = str_replace('{kdebug-classes}', 'khidden', $template);
      $template = str_replace('{kconfiguration-classes}', 'kactive', $template);
      $template = str_replace('{klinks-classes}', '', $template);
    }
    else {
      $template = str_replace('{kdebug-classes}', 'kactive', $template);
      $template = str_replace('{kconfiguration-classes}', '', $template);
      $template = str_replace('{klinks-classes}', '', $template);
    }

    return $template;
  }

  /**
   * {@inheritDoc}
   */
  Public static function renderFooter($caller, $config_output, $config_only = FALSE) {
    $template = parent::renderFooter($caller, $config_output);

    // Doing special stuff for smoky-grey:
    // We hide the debug-tab when we are displaying the config-only and switch
    // to the config as the current payload.
    if ($config_only) {
      $template = str_replace('{kconfiguration-classes}', '', $template);
    }
    else {
      $template = str_replace('{kconfiguration-classes}', 'khidden', $template);
    }

    return $template;
  }

  /**
   * {@inheritDoc}
   */
  public static function renderFatalMain($type, $errstr, $errfile, $errline, $source) {
    $template = parent::renderFatalMain($type, $errstr, $errfile, $errline, $source);

    // Add the search.
    $template = str_replace('{search}', self::renderSearch(), $template);
    return str_replace('{KrexxId}', Analysis\Hive::getMarker(), $template);
  }

}
