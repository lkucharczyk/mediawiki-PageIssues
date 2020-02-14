<?php

namespace MediaWiki\Extension\PageIssues\Models;

use Title;

class PageIssues {
	/** @var \Title */
	public $page;
	/** @var PageIssue[] */
	public $issues = [];
	/** @var Report[] */
	public $reports = [];

	public function __construct( Title $page ) {
		$this->page = $page;
	}

	public function addReport( Report $report ) : bool {
		if ( $report->page->getArticleID() !== $this->page->getArticleID() ) {
			return false;
		}

		$out = false;
		foreach ( $report->issues as $issue ) {
			if ( !array_key_exists( $issue, $this->issues ) ) {
				$this->issues[$issue] = new PageIssue( $this->page, $issue );
			}

			if ( $this->issues[$issue]->addReport( $report ) ) {
				$this->reports[] = $report;
				$out = true;
			}
		}

		return $out;
	}

	public function getAll() {
		$reports = Report::getAll( [ 'pir_page' => $this->page->getArticleID() ] );

		foreach ( $reports as $report ) {
			$this->addReport( $report );
		}
	}
}
