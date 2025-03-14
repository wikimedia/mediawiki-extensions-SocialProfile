<?php

// @phan-suppress-next-line PhanUndeclaredExtendedClass
class SocialProfileResponsiveRLModuleBundleSizeTest extends MediaWiki\Tests\Structure\BundleSizeTestBase {

	/** @inheritDoc */
	public function getBundleSizeConfig(): string {
		return dirname( __DIR__, 3 ) . '/UserGifts/bundlesize.config.json';
	}
}
