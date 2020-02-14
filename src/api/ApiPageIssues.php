<?php

namespace MediaWiki\Extension\PageIssues\Api;

use ApiBase;
use ApiModuleManager;
use MediaWiki\Extension\PageIssues\Models\DatabaseModel;
use Title;

class ApiPageIssues extends ApiBase {
	/** @var ApiModuleManager */
	protected $moduleManager;

	/** @var array */
	public $params;

	/** @var Title */
	public $title;

	public function __construct( \ApiMain $main, $moduleName ) {
		parent::__construct( $main, $moduleName );

		$this->moduleManager = new ApiModuleManager( $this );
		$this->moduleManager->addModules( [
			ApiReport::MODULE_NAME => ApiReport::class,
			ApiUpvote::MODULE_NAME => ApiUpvote::class,
			ApiRemoveUpvote::MODULE_NAME => ApiRemoveUpvote::class
		], 'piaction' );
	}

	public function execute() {
		$this->params = $this->extractRequestParams();

		$this->title = Title::newFromID( $this->params['pageid'] );
		if ( !$this->title || !$this->title->exists() ) {
			$this->dieWithError( [ 'apierror-nosuchpageid', $this->params['pageid'] ] );
		}

		if ( !DatabaseModel::isValidTitle( $this->title ) ) {
			$this->dieWithError( 'pageissues-invalidtitle' );
		}

		$module = $this->moduleManager->getModule( $this->params['piaction'] );
		$module->execute();
	}

	public function getAllowedParams() : array {
		return [
			'pageid' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true
			],
			'piaction' => [
				ApiBase::PARAM_TYPE => 'submodule',
				ApiBase::PARAM_REQUIRED => true
			]
		];
	}

	public function getModuleManager() : ApiModuleManager {
		return $this->moduleManager;
	}

	public function needsToken() : string {
		return 'csrf';
	}
}
