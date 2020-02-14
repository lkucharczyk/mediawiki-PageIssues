<?php

namespace MediaWiki\Extension\PageIssues\Models;

use Title;
use User;
use Wikimedia\Rdbms\IDatabase;

abstract class DatabaseModel {
	public const TABLE = '';
	public const COLPREFIX = '';

	/** @var int */
	public $id;
	/** @var \Title */
	public $page;
	/** @var \User */
	public $user;
	/** @var string */
	public $timestamp;

	protected function __construct( array $data = [] ) {
		$this->id = $data[static::COLPREFIX . 'id'] ?? $data['id'] ?? 0;

		$this->page = $data[static::COLPREFIX . 'page'] ?? $data['page'] ?? 0;
		if ( !$this->page instanceof Title ) {
			$this->page = Title::newFromId( $this->page );
		}

		$this->user = $data[static::COLPREFIX . 'actor'] ?? $data['actor'] ?? 0;
		if ( !$this->user instanceof User ) {
			$this->user = User::newFromActorId( $this->user );
		}

		$this->timestamp = wfTimestamp( TS_UNIX, $data[static::COLPREFIX . 'timestamp'] ?? 0 );
	}

	/**
	 * @return array
	 */
	public function toDatabaseRow( IDatabase $dbw = null ) : array {
		$dbw = $dbw ?? wfGetDB( DB_MASTER );
		$out = [
			static::COLPREFIX . 'page' => $this->page->getArticleID(),
			static::COLPREFIX . 'actor' => $this->user->getActorId( $dbw ),
			static::COLPREFIX . 'timestamp' => $dbw->timestamp( $this->timestamp )
		];

		if ( $this->id !== 0 ) {
			$out[static::COLPREFIX . 'id'] = $this->id;
		}

		return $out;
	}

	/**
	 * @return bool
	 */
	public function update( IDatabase $dbw = null ) : bool {
		if ( $this->id === 0 ) {
			return false;
		}

		$dbw = $dbw ?? wfGetDB( DB_MASTER );
		$dbw->update( static::TABLE, $this->toDatabaseRow( $dbw ), [ static::COLPREFIX . 'id' => $this->id ] );

		return true;
	}

	/**
	 * @return bool
	 */
	public function delete( IDatabase $dbw = null ) : bool {
		if ( $this->id === 0 ) {
			return false;
		}

		$dbw = $dbw ?? wfGetDB( DB_MASTER );
		$dbw->delete( static::TABLE, [ static::COLPREFIX . 'id' => $this->id ] );

		$this->id = -1;

		return true;
	}

	/**
	 * @return static
	 */
	public static function createNew( array $data, IDatabase $dbw = null ) {
		$object = new static( $data );

		$dbw = $dbw ?? wfGetDB( DB_MASTER );
		$dbw->insert( static::TABLE, $object->toDatabaseRow( $dbw ) );
		$object->id = $dbw->insertId();

		return $object;
	}

	/**
	 * @return static[]
	 */
	public static function getAll( $conditions = [], IDatabase $dbr = null ) : array {
		$out = [];

		$dbr = $dbr ?? wfGetDB( DB_REPLICA );
		$res = $dbr->select( static::TABLE, '*', $conditions );

		foreach ( $res as $row ) {
			$out[] = new static( (array)$row );
		}

		return $out;
	}

	/**
	 * @return static|null
	 */
	public static function getByID( int $id, IDatabase $dbr = null ) {
		$dbr = $dbr ?? wfGetDB( DB_REPLICA );
		$res = $dbr->select( static::TABLE, '*', [ static::COLPREFIX . 'id' => $id ] );

		return $res ? new static( (array)$res ) : null;
	}

	public static function isValidTitle( Title $title ) : bool {
		return $title instanceof Title && $title->exists() && $title->isContentPage() && !$title->isMainPage();
	}
}
