<?php

namespace MediaWiki\Extension\PageIssues\Api;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use MediaWiki\Extension\PageIssues\Models\Upvote;
use MediaWiki\Extension\PageIssues\Specials\SpecialMostUpvoted;

class ApiPropUpvotes extends ApiQueryBase {
	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'piu' );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$res = $this->getResult();

		$pages = [];
		foreach ( $this->getPageSet()->getGoodTitles() as $id => $page ) {
			if ( !Upvote::isValidTitle( $page ) ) {
				continue;
			}

			$pages[$id] = $page;
		}

		if ( $pages === [] ) {
			return;
		}

		$dbr = $this->getDB();
		$dres = $dbr->select(
			Upvote::TABLE,
			[
				'page' => Upvote::COLPREFIX . 'page',
				'count' => 'COUNT(*)'
			],
			[ Upvote::COLPREFIX . 'page' => array_keys( $pages ) ],
			[ Upvote::COLPREFIX . 'timestamp > ' . wfTimestamp( TS_MW, ( wfTimestamp() - $params['days'] * 86400 ) ) ],
			__METHOD__,
			[ 'GROUP BY' => Upvote::COLPREFIX . 'page' ]
		);

		foreach ( $dres as $row ) {
			$res->addValue( [ 'query', 'pages', $row->page ], 'upvotes', $row->count );
		}
	}

	public function getAllowedParams() {
		return [
			'days' => [
				ApiBase::PARAM_TYPE => SpecialMostUpvoted::ALLOWED_DAYS,
				ApiBase::PARAM_DFLT => 30,
				ApiBase::PARAM_REQUIRED => true
			]
		];
	}
}
