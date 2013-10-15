<?php
/**
 * Plugin Name: Before/After Image Creator
 * Description: Adds an interface to create before/after images
 * Author: Sennza Pty Ltd, Bronson Quick, Ryan McCue, Lachlan MacPherson
 * Author URI: http://www.sennza.com.au/
 * Version: 0.1
 */

include( dirname( __FILE__ ) . '/merger.php' );

Sennza_Cosmos_BeforeAfter::bootstrap();

class Sennza_Cosmos_BeforeAfter {
	public static function bootstrap() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
	}

	public static function add_menu() {
		add_media_page(
			__( 'Before/After Image Creator', 'sz_cosmos' ),
			__( 'Before/After', 'sz_cosmos' ),
			'upload_files',
			'sz_beforeafter',
			array( __CLASS__, 'admin_page' )
		);
	}

	public static function admin_page() {
?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e( 'Before/After Image Creator', 'sz_cosmos' ) ?></h2>
	<p><?php _e( 'Note: Both images must be 496px wide and have the same height.', 'sz_cosmos' ) ?></p>
<?php
		if ( isset( $_POST['sz_cmos_ba_name'] ) ) {
			if ( self::handle_upload() ) {
				echo '</div>';
				return;
			}
		}
?>
	<form action="" method="POST" enctype="multipart/form-data">
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Before Image', 'sz_cosmos' ) ?></th>
				<td><input type="file" name="sz_cmos_ba_first" /></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'After Image', 'sz_cosmos' ) ?></th>
				<td><input type="file" name="sz_cmos_ba_second" /></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Merged Filename', 'sz_cosmos' ) ?></th>
				<td><input type="name" name="sz_cmos_ba_name" />
					<p class="description"><?php _e( "Don't include the .jpg extension here.", 'sz_cosmos' ) ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Create Merged Image', 'sz_cosmos' ) ?>" />
		</p>
	</form>
</div>
<?php
	}

	protected static function handle_upload() {
		$name = $_POST['sz_cmos_ba_name'] . '.jpg';
		if (
			empty( $_FILES['sz_cmos_ba_first']['tmp_name'] )
			|| empty( $_FILES['sz_cmos_ba_second']['tmp_name'] )
		) {
			echo '<div class="error"><p>' . __( 'Both the before and after files must be uploaded', 'sz_cosmos' ) . '</p></div>';

			if ( ! empty( $_FILES['sz_cmos_ba_first']['tmp_name'] ) )
				unlink( $_FILES['sz_cmos_ba_first']['tmp_name'] );

			if ( ! empty( $_FILES['sz_cmos_ba_second']['tmp_name'] ) )
				unlink( $_FILES['sz_cmos_ba_second']['tmp_name'] );

			return false;
		}

		$base = __DIR__ . '/base.jpg';

		$first  = $_FILES['sz_cmos_ba_first']['tmp_name'];
		$second = $_FILES['sz_cmos_ba_second']['tmp_name'];
		$merger = new Sennza_Cosmos_ImageMerger($base, $first, $second);

		$merge = $merger->merge();
		if ( is_wp_error( $merge ) ) {
			echo '<div class="error"><p>' . esc_html( $merge->get_error_message() ) . '</p></div>';
			unlink($first);
			unlink($second);
			return false;
		}

		$id = $merger->add_to_media( $name );
		if ( is_wp_error($id) ) {
			echo '<div class="error"><p>' . esc_html( $id->get_error_message() ) . '</p></div>';
			unlink($first);
			unlink($second);
			return false;
		}

		$img = wp_get_attachment_image( $id, 'full' );
?>
	<div class="alert"><p><?php
		printf(
			__( 'Your merged image has been created and is shown below! You may want to <a href="%s">add it to a gallery</a> now.', 'sz_cosmos' ),
			admin_url('edit.php?post_type=page')
		)
		?></p></div>

	<div class="image-holder">
		<?php echo $img ?>
	</div>
<?php

		return true;
	}
}
