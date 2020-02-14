<?php

namespace MediaWiki\Extension\PageIssues\Api;

use MediaWiki\Extension\PageIssues\Models\Upvote;

class ApiUpvote extends ApiPageIssuesBase {
	const MODULE_NAME = 'upvote';

	public function execute() {
		$params = $this->extractRequestParams();
		$res = $this->getResult();
		$user = $this->getContext()->getUser();

		$this->checkUserRightsAny( 'pageissues-upvote' );

		if ( $user->pingLimiter( self::MODULE_NAME ) ) {
			$this->dieWithError( 'apierror-ratelimited' );
		}

		$out = Upvote::createNew( [
			'page' => $this->piModule->title,
			'actor' => $user
		] );

		if ( $out ) {
			$res->addValue( null, 'success', true );
		} else {
			$this->dieWithError( 'apierror-unknownerror' );
		}
	}
}
