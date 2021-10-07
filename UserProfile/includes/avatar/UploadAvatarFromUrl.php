<?php
/**
 * Based on QuizGame's QuizFileUploadFromUrl (which I also wrote) as of 6 October 2021,
 * but modified to remove the "prefix file name with current time()" stuff.
 *
 * @ingroup Upload
 */
class UploadAvatarFromUrl extends UploadFromUrl {
	use UploadAvatarTrait;

	/**
	 * Create a form of UploadBase depending on wpSourceType and initializes it
	 *
	 * @param WebRequest &$request
	 * @param string|null $type
	 * @return UploadAvatarFromUrl
	 */
	public static function createFromRequest( &$request, $type = null ) {
		$handler = new self;
		$handler->initializeFromRequest( $request );
		return $handler;
	}

	/** @inheritDoc */
	public function doStashFile( User $user = null ) {
		return parent::doStashFile( $user );
	}

}
