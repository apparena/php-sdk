<?php
namespace AppArena\Models\CSSCompiler;


interface CSSCompilerInterface {

	/**
	 * Compiles all content (files and content) to CSS output
	 * @return bool
	 */
	public function compile();


}