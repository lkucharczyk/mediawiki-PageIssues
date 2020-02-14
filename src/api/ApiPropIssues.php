<?php

namespace MediaWiki\Extension\PageIssues\Api;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use ApiResult;
use MediaWiki\Extension\PageIssues\Models\Report;
use MediaWiki\Extension\PageIssues\Models\PageIssuesSet;

class ApiPropIssues extends ApiQueryBase {
	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'pir' );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$res = $this->getResult();

		$pages = [];
		foreach ( $this->getPageSet()->getGoodTitles() as $id => $page ) {
			if ( !Report::isValidTitle( $page ) ) {
				continue;
			}

			$pages[$id] = $page;
		}

		if ( $pages === [] ) {
			return;
		}

		$conditions = [ Report::COLPREFIX . 'page' => array_keys( $pages ) ];
		if ( !$params['reportsminor'] ) {
			$conditions[] = Report::COLPREFIX . 'note != ""';
		}
		if ( !$params['reportsold'] ) {
			$conditions[] = Report::COLPREFIX . 'timestamp > ' . wfTimestamp( TS_MW, wfTimestamp() - 30 * 86400 );
		}

		$pageissuesset = new PageIssuesSet();
		$pageissuesset->getAll( $conditions );

		foreach ( $pageissuesset->pageissues as $pageissues ) {
			$issues = [];
			foreach ( $pageissues->issues as $pageissue ) {
				$issue = [];

				if ( in_array( 'quantity', $params['prop'] ) ) {
					$issue['quantity'] = $pageissue->geReportCount();
				}

				if ( in_array( 'reports', $params['prop'] ) ) {
					$reports = [];

					foreach ( $pageissue->reports as $report ) {
						$reportdata = [];

						if ( in_array( 'userid', $params['reportsprop'] ) ) {
							$reportdata['userid'] = $report->user->getId();
						}
						if ( in_array( 'note', $params['reportsprop'] ) ) {
							$reportdata['note'] = $report->note;
						}
						if ( in_array( 'revisionage', $params['reportsprop'] ) ) {
							$reportdata['revisionage'] = $report->getRevisionAge();
						}
						if ( in_array( 'timestamp', $params['reportsprop'] ) ) {
							$reportdata['timestamp'] = wfTimestamp( TS_UNIX, $report->timestamp );
						}

						$reports[] = $reportdata;
					}

					$issue['reports'] = $reports;
				}

				$issues[$pageissue->issue] = $issue;
			}

			ApiResult::setArrayTypeRecursive( $issues, 'assoc' );
			$res->addValue( [ 'query', 'pages', $pageissues->page->getArticleId() ], 'issues', $issues );
		}
	}

	/**
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'prop' => [
				ApiBase::PARAM_TYPE => [ 'quantity', 'reports' ],
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_DFLT => [ 'quantity', 'reports' ]
			],
			'reportsold' => [
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false
			],
			'reportsminor' => [
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false
			],
			'reportsprop' => [
				ApiBase::PARAM_TYPE => [ 'userid', 'note', 'revisionage', 'timestamp' ],
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_DFLT => [ 'note' ]
			]
		];
	}
}
