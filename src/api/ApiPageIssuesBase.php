<?php

namespace MediaWiki\Extension\PageIssues\Api;

use ApiBase;
use Title;

abstract class ApiPageIssuesBase extends ApiBase {
	const MODULE_NAME = '';

	/** @var ApiPageIssues */
	protected $piModule;

	public function __construct( ApiPageIssues $piModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $piModule->getMain(), $moduleName, $modulePrefix );
		$this->piModule = $piModule;
	}

	/**
	 * @return ApiPageIssues
	 */
	public function getParent() : ApiPageIssues {
		return $this->piModule;
	}

	/**
	 * @return \Title
	 */
	public function getTitle() : Title {
		return $this->getParent()->title;
	}
}
