<?php
/**
 * Created by PhpStorm.
 * User: s.buckpesch
 * Date: 10.04.2017
 * Time: 10:14
 */

namespace AppArena\Models\CSSCompiler;


class AbstractCSSCompiler implements CSSCompilerInterface {

	protected $content   = []; // Content to compile
	protected $files     = []; // Files to compile
	protected $variables = []; // Variables to replace in the uncompiled content



}