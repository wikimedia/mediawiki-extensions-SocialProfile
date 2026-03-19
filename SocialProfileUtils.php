<?php

use MediaWiki\Shell\Shell;

class SocialProfileUtils {
	static function runImageMagickShell( array $options ) {
		global $wgImageMagickConvertCommand;
		Shell::command(
			$wgImageMagickConvertCommand,
			...$options
		)->execute();
	}
}
