<?php

namespace MediaWiki\Extension\PageIssues\Models;

use Title;
use User;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\IDatabase;

class Upvote extends DatabaseModel {
	public const TABLE = 'pageissues_upvote';
	public const COLPREFIX = 'piu_';

	/**
	 * @return static|null
	 */
	public static function createNew( $data, ?IDatabase $dbw = null ) {
		try {
			return parent::createNew( $data, $dbw );
		} catch ( DBQueryError $e ) {
			if ( $e->errno !== 1062 ) { // ER_DUP_ENTRY
				throw $e;
			}

			return null;
		}
	}

	/**
	 * @return static|null
	 */
	public static function getByUserAndPage( User $user, Title $page ) {
		if ( !static::isValidTitle( $page ) ) {
			return null;
		}

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select( static::TABLE, '*', [
			static::COLPREFIX . 'page' => $page->getArticleID(),
			static::COLPREFIX . 'actor' => $user->getActorId()
		] )->current();

		return $res ? new static( (array)$res ) : null;
	}

	public static function getMostUpvoted( $days = 30, IDatabase $dbr = null ) : array {
		$out = [];

		$dbr = $dbr ?? wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			static::TABLE,
			[
				'page' => static::COLPREFIX . 'page',
				'count' => 'COUNT(*)'
			],
			[ static::COLPREFIX . 'timestamp > ' . wfTimestamp( TS_MW, ( wfTimestamp() - $days * 86400 ) ) ],
			__METHOD__,
			[
				'GROUP BY' => static::COLPREFIX . 'page',
				'ORDER BY' => [ 'count DESC' ]
			]
		);

		foreach ( $res as $row ) {
			$out[] = [
				'page' => Title::newFromID( $row->page ),
				'count' => $row->count
			];
		}

		return $out;
	}
}
