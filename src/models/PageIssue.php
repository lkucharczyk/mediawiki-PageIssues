<?php

namespace MediaWiki\Extension\PageIssues\Models;

use Message;
use Title;

class PageIssue {
	/** @var \Title */
	public $page;
	/** @var string */
	public $issue;
	/** @var Report[] */
	public $reports = [];

	public function __construct( Title $page, string $issue ) {
		$this->page = $page;
		$this->issue = $issue;
	}

	public function addReport( Report $report ) : bool {
		if ( in_array( $this->issue, $report->issues ) ) {
			$this->reports[] = $report;
			return true;
		}

		return false;
	}

	public function getIssueMessage() : Message {
		return wfMessage( 'pageissues-issue-' . $this->issue );
	}

	public function geReportCount() : int {
		return count( $this->reports );
	}

	public function getNotes() : array {
		$out = [];

		foreach ( $this->reports as $report ) {
			if ( $report->note ) {
				$out[] = [
					'user' => $report->user,
					'note' => $report->note,
					'revisionage' => $report->getRevisionAge(),
					'timestamp' => $report->timestamp
				];
			}
		}

		usort( $out, function ( $a, $b ) {
			return $b['timestamp'] - $a['timestamp'];
		} );

		return $out;
	}

	public function getMinorReportsData() : array {
		$out = [
			'current' => [
				'count' => 0,
				'lastuser' => null,
				'timestamp_earliest' => wfTimestamp( TS_UNIX ),
				'timestamp_latest' => null
			],
			'old' => [
				'count' => 0,
				'lastuser' => null,
				'timestamp_earliest' => wfTimestamp( TS_UNIX ),
				'timestamp_latest' => null,
				'age' => -1
			]
		];

		$users = [
			'current' => [],
			'old' => []
		];

		foreach ( $this->reports as $report ) {
			if ( $report->note ) {
				continue;
			}

			$age = $report->getRevisionAge();
			$key = $age > 0 ? 'old' : 'current';

			$userid = $report->user->getID();
			if ( in_array( $userid, $users[$key] ) ) {
				continue;
			}
			$users[$key][] = $userid;
			$out[$key]['count']++;

			if ( $age > 0 && ( $out[$key]['age'] === -1 || $out[$key]['age'] > $age ) ) {
				$out[$key]['age'] = $age;
			}
			if ( $out[$key]['timestamp_earliest'] > $report->timestamp ) {
				$out[$key]['timestamp_earliest'] = $report->timestamp;
			}
			if ( $out[$key]['timestamp_latest'] < $report->timestamp ) {
				$out[$key]['timestamp_latest'] = $report->timestamp;

				if ( $out[$key]['lastuser'] === null || !$report->user->isAnon() ) {
					$out[$key]['lastuser'] = $report->user;
				}
			}
		}

		return $out;
	}

	public function getSortKey() : string {
		return $this->page->getPrefixedDBkey() . '|' . $this->getIssueMessage()->plain();
	}
}
