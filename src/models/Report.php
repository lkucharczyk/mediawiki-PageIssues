<?php

namespace MediaWiki\Extension\PageIssues\Models;

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use Wikimedia\Rdbms\IDatabase;

class Report extends DatabaseModel {
	public const TABLE = 'pageissues_report';
	public const COLPREFIX = 'pir_';

	/** @var RevisionRecord */
	protected $revision;
	/** @var string[] */
	public $issues;
	/** @var string */
	public $note;

	protected function __construct( array $data = [] ) {
		parent::__construct( $data );

		$this->revision = $data[static::COLPREFIX . 'revision'] ?? $data['revision'] ?? $this->page->getLatestRevID();
		if ( !$this->revision instanceof RevisionRecord ) {
			$this->revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById( $this->revision );
		}

		$this->issues = $data[static::COLPREFIX . 'issues'] ?? $data['issues'] ?? [];
		$this->note = $data[static::COLPREFIX . 'note'] ?? $data['note'] ?? '';

		if ( is_string( $this->issues ) ) {
			$this->issues = explode( '|', $this->issues );
		}
	}

	public function toDatabaseRow( ?IDatabase $dbw = null ) : array {
		$out = parent::toDatabaseRow( $dbw );

		$out[static::COLPREFIX . 'revision'] = $this->revision->getId();
		$out[static::COLPREFIX . 'issues'] = implode( '|', $this->issues );
		$out[static::COLPREFIX . 'note'] = $this->note;

		return $out;
	}

	public function getRevisionAge() : int {
		$revstore = MediaWikiServices::getInstance()->getRevisionStore();
		$comparedID = $this->page->getLatestRevID();
		$revisionID = $this->revision->getId();
		$age = 0;

		while ( $revisionID !== $comparedID || $comparedID === null ) {
			$comparedID = $revstore->getRevisionById( $comparedID )->getParentId();
			++$age;
		}

		return $age;
	}
}
