<?php

namespace Monei\Templates;

class TemplateManager {

	/**
	 * @var TemplateInterface[]
	 */
	private array $templates = array();

	/**
	 * @param TemplateInterface[] $templates Keyed array of templateName => TemplateInstance
	 */
	public function __construct( array $templates ) {
		$this->templates = $templates;
	}

	/**
	 * Retrieve the template instance by name.
	 *
	 * @param string $templateName
	 * @return TemplateInterface|null
	 */
	public function getTemplate( string $templateName ): ?TemplateInterface {
		return $this->templates[ $templateName ] ?? null;
	}
}
