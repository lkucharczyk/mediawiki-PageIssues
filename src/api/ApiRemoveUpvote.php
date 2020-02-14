<?php

namespace MediaWiki\Extension\PageIssues\Api;

use MediaWiki\Extension\PageIssues\Models\Upvote;

class ApiRemoveUpvote extends ApiPageIssuesBase {
	const MODULE_NAME = 'removeupvote';

	public function execute() {
		$params = $this->extractRequestParams();
		$res = $this->getResult();
		$user = $this->getContext()->getUser();

		$this->checkUserRightsAny( 'pageissues-upvote' );

		if ( $user->pingLimiter( ApiUpvote::MODULE_NAME ) ) {
			$this->dieWithError( 'apierror-ratelimited' );
		}

		$obj = Upvote::getByUserAndPage( $user, $this->getTitle() );
		if ( $obj instanceof Upvote ) {
			$success = $obj->delete();
		}

		if ( $success ) {
			$res->addValue( null, 'success', true );
		} else {
			$this->dieWithError( 'apierror-unknownerror' );
		}
	}
}
