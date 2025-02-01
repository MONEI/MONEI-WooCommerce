<?php

namespace Monei\Templates;

interface TemplateInterface {

	/**
	 * Render or echo the HTML for this template.
	 *
	 * @param array $data Data needed in the template to render
	 * @return void
	 */
	public function render( array $data ): void;
}
