<?php

class Sennza_Cosmos_ImageMerger {
	/**
	 * Construct an image merger
	 *
	 * @param string $first First image file
	 * @param string $second Second image file
	 */
	public function __construct($base, $first, $second) {
		$this->base = $base;
		$this->first = $first;
		$this->second = $second;
	}

	/**
	 * Merge the two images into one using ImageMagick
	 *
	 * @return boolean Successfulness
	 */
	public function merge() {
		$first = new Imagick( $this->first );
		$second = new Imagick( $this->second );
		$width = $first->getImageWidth();
		if ( $second->getImageWidth() !== $width || $width !== 496 ) {
			return new WP_Error( 'sz_cosmos_unequal_width', __( 'Both images must be 496px wide and have the same height', 'sz_cosmos' ) );
		}

		$height = $first->getImageHeight();
		if ( $second->getImageHeight() !== $height ) {
			return new WP_Error( 'sz_cosmos_unequal_height', __( 'Both images must be 496px wide and have the same height', 'sz_cosmos' ) );
		}

		$base = new Imagick( $this->base );
		$all = new Imagick();
		$all->addImage( $first );
		$all->addImage( $second );
		$all->resetIterator();
		$merged = $all->appendImages(false);

		$merged->addImage( $base );
		$merged->resetIterator();
		$withfooter = $merged->appendImages(true);

		$withfooter->setImageFormat('jpeg');
		$this->merged = $withfooter;
		return true;
	}

	/**
	 * Add the image to the Media Library
	 *
	 * @param string $file Filename (including extension)
	 * @return WP_Error|int WP_Error instance on failure, or attachment ID on success
	 */
	public function add_to_media($file) {
		$tmpfname = wp_tempnam($file);

		if ( ! $this->merged->writeImage($tmpfname) )
			return new WP_Error( 'sz_cosmos_cannot_write', __( 'Unable to write the merged image to a file', 'sz_cosmos' ) );

		// Set variables for storage
		// fix file filename for query strings
		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $tmpfname, $matches );
		$file_array = array();
		$file_array['name'] = basename($file);
		$file_array['tmp_name'] = $tmpfname;

		// If error storing temporarily, unlink
		if ( is_wp_error( $tmpfname ) ) {
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
		}

		// do the validation and storage stuff
		$id = media_handle_sideload( $file_array, 0 );
		// If error storing permanently, unlink
		if ( is_wp_error($id) ) {
			@unlink($file_array['tmp_name']);
		}

		return $id;
	}
}