<?php

namespace MediaWiki\Extension\PageIssues\Api;

use ApiBase;
use MediaWiki\Extension\PageIssues\Models\Report;
use MediaWiki\Extension\PageIssues\Models\Issues;

class ApiReport extends ApiPageIssuesBase {
	const MODULE_NAME = 'report';

	public function execute() {
		$params = $this->extractRequestParams();
		$res = $this->getResult();
		$user = $this->getContext()->getUser();

		// In case someone enters only an unsupported issue type
		if ( $params['issues'] === [] ) {
			$this->dieWithError( [ 'apierror-missingparam', 'issues' ] );
		}

		$this->checkUserRightsAny( 'pageissues-report' );

		if ( $user->pingLimiter( self::MODULE_NAME ) ) {
			$this->dieWithError( 'apierror-ratelimited' );
		}

		$out = Report::createNew( [
			'page' => $this->piModule->title,
			'actor' => $user,
			'issues' => $params['issues'],
			'note' => $params['note'] ?? ''
		] );

		if ( $out ) {
			$res->addValue( null, 'success', true );
		} else {
			$this->dieWithError( 'apierror-unknownerror' );
		}
	}

	public function getAllowedParams() {
		return [
			'issues' => [
				ApiBase::PARAM_TYPE => Issues::getAll(),
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_REQUIRED => true
			],
			'note' => [
				ApiBase::PARAM_TYPE => 'string'
			]
		];
	}
}
