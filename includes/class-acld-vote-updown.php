<?php
/**
 *
 * Main logic vote/downvote theme.
 *
 * @package bp-activity-comment-like-dislike
 */

/**
 * Class-ACLD_Vote_UpDown class
 *
 * @package bp-activity-comment-like-dislike
 */
class ACLD_Vote_UpDown {
	/**
	 * Construct function.
	 */
	public function __construct() {
		register_activation_hook( ACLD_PLUGIN_FILE, array( $this, 'acld_updown_votes_create_db' ) );
		add_action( 'bp_activity_comment_options', array( $this, 'acld_comment_options_display_custom' ), 15 );
		add_action( 'wp_enqueue_scripts', array( $this, 'acld_bp_scripts' ) );
		add_action( 'wp_ajax_buddypress_user_like', array( $this, 'acld_bp_user_like' ) );
		add_action( 'wp_ajax_buddypress_user_dislike', array( $this, 'acld_bp_user_dislike' ) );
	}
	/**
	 * Create db function.
	 *
	 * @return void
	 */
	public function acld_updown_votes_create_db() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$updown_table    = $wpdb->prefix . 'bb_updown_votes';

		$sql = "CREATE TABLE $updown_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            comment_id mediumint(9) NOT NULL,
            activity_id mediumint(9) NOT NULL,
            vote_type varchar(55) NOT NULL,
            user_id mediumint(9) NOT NULL,
            secondary_item_id mediumint(9) NOT NULL,
            vote_status mediumint(9) NOT NULL,
            PRIMARY KEY id (id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
	/**
	 * Add html for vote/donwvote.
	 *
	 * @return void
	 */
	public function acld_comment_options_display_custom() {
		$like_count    = $this->acld_bp_get_count( bp_get_activity_comment_id(), bp_get_activity_id(), 'like', bp_get_activity_secondary_item_id() );
		$dislike_count = $this->acld_bp_get_count( bp_get_activity_comment_id(), bp_get_activity_id(), 'dislike', bp_get_activity_secondary_item_id() );
		echo "<span class='like_text' class='like_text' data-cmt-id=" . esc_attr( bp_get_activity_comment_id() ) . ' data-act-id=' . esc_attr( bp_get_activity_id() ) . ' data-act-snd-id=' . esc_attr( bp_get_activity_secondary_item_id() ) . ' data-usr-id=' . esc_attr( get_current_user_id() ) . "  data-type='like'>Like</span> (<span class='like_count'>" . esc_html( $like_count ) . "</span>) 
        <span class='dislike_text' class='like_text' data-cmt-id=" . esc_attr( bp_get_activity_comment_id() ) . ' data-act-id=' . esc_attr( bp_get_activity_id() ) . ' data-act-snd-id=' . esc_attr( bp_get_activity_secondary_item_id() ) . ' data-usr-id=' . esc_attr( get_current_user_id() ) . "  data-type='dislike'>Dislike</span> (<span class='dislike_count'>" . esc_html( $dislike_count ) . '</span>)';
	}
	/**
	 * Scripts added.
	 *
	 * @return void
	 */
	public function acld_bp_scripts() {
		wp_enqueue_script( 'script-js', ACLD_PLUGIN_URL . '/assets/js/script.js', array( 'jquery' ), '1.0.0', true );
		wp_localize_script( 'script-js', 'frontend_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * Add entry for vote/like.
	 *
	 * @return void
	 */
	public function acld_bp_user_like() {
		if ( ! empty( $_POST['comment_id'] ) && ! empty( $_POST['activity_id'] ) || ! empty( $_POST['activity_snd_id'] ) && ! empty( $_POST['user_id'] ) && ! empty( $_POST['type'] ) ) {
			$comment_id      = sanitize_text_field( $_POST['comment_id'] );
			$activity_id     = sanitize_text_field( $_POST['activity_id'] );
			$activity_snd_id = sanitize_text_field( $_POST['activity_snd_id'] );
			$user_id         = sanitize_text_field( $_POST['user_id'] );
			$type            = sanitize_key( $_POST['type'] );
			global $wpdb;

			$updown_table = "{$wpdb->prefix}bb_updown_votes";

			$vote_status = 0;

			$like_exist = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(%s) as like_count,id,vote_status from $updown_table where comment_id = %d and user_id = %d and vote_type = %s",
					'id',
					$comment_id,
					$user_id,
					$type
				),
				ARRAY_A
			);

			$like_id     = sanitize_text_field( $like_exist[0]['id'] );
			$like_status = sanitize_text_field( $like_exist[0]['vote_status'] );
			$like_count  = sanitize_text_field( $like_exist[0]['like_count'] );

			if ( '1' === $like_exist[0]['like_count'] && '0' === $like_exist[0]['vote_status'] ) {
				$vote_status  = 1;
				$updown_table = "{$wpdb->prefix}bb_updown_votes";
				$wpdb->query(
					$wpdb->prepare(
						"update $updown_table set vote_status =%d where id = %d",
						$vote_status,
						$like_id,
					)
				);
			} elseif ( '1' === $like_exist[0]['like_count'] && '1' === $like_exist[0]['vote_status'] ) {
				$vote_status  = 0;
				$updown_table = "{$wpdb->prefix}bb_updown_votes";
				$wpdb->query(
					$wpdb->prepare(
						"update $updown_table set vote_status =%d where id = %d",
						$vote_status,
						$like_id,
					)
				);
			} else {
				$vote_status = 1;
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO $updown_table ( comment_id, activity_id, vote_type,user_id,secondary_item_id,vote_status ) VALUES ( %d, %d, %s, %d, %d,%d)",
						array(
							$comment_id,
							$activity_id,
							$type,
							$user_id,
							$activity_snd_id,
							$vote_status,
						)
					)
				);
			}

			$like_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(%s) from $updown_table where comment_id = %d and activity_id = %d and vote_type = %s and secondary_item_id = %d and vote_status = %d",
					'id',
					$comment_id,
					$activity_id,
					$type,
					$activity_snd_id,
					1
				)
			);

			$response = array(
				'like_count' => $like_count,
				'comment_id' => $comment_id,
			);
			echo wp_json_encode( $response );
			exit;
		}
	}
	/**
	 * Add entry for downvote/dislike.
	 *
	 * @return void
	 */
	public function acld_bp_user_dislike() {
		if ( ! empty( $_POST['comment_id'] ) && ! empty( $_POST['activity_id'] ) || ! empty( $_POST['activity_snd_id'] ) && ! empty( $_POST['user_id'] ) && ! empty( $_POST['type'] ) ) {
			$comment_id      = sanitize_text_field( $_POST['comment_id'] );
			$activity_id     = sanitize_text_field( $_POST['activity_id'] );
			$activity_snd_id = sanitize_text_field( $_POST['activity_snd_id'] );
			$user_id         = sanitize_text_field( $_POST['user_id'] );
			$type            = sanitize_key( $_POST['type'] );
			global $wpdb;
			$updown_table = "{$wpdb->prefix}bb_updown_votes";

			$vote_status = 0;

			$dislike_exist  = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(%s) as dislike_count,id,vote_status from $updown_table where comment_id = %d and user_id = %d and vote_type = %s",
					'id',
					$comment_id,
					$user_id,
					$type
				),
				ARRAY_A
			);
			$dislike_id     = sanitize_text_field( $dislike_exist[0]['id'] );
			$dislike_status = sanitize_text_field( $dislike_exist[0]['vote_status'] );
			$dislike_count  = sanitize_text_field( $dislike_exist[0]['dislike_count'] );

			if ( '1' === $dislike_exist[0]['dislike_count'] && '0' === $dislike_exist[0]['vote_status'] ) {
				$vote_status  = 1;
				$updown_table = "{$wpdb->prefix}bb_updown_votes";
				$wpdb->query(
					$wpdb->prepare(
						"update $updown_table set vote_status =%d where id = %d",
						$vote_status,
						$dislike_id,
					)
				);
			} elseif ( '1' === $dislike_exist[0]['dislike_count'] && '1' === $dislike_exist[0]['vote_status'] ) {
				$vote_status  = 0;
				$updown_table = "{$wpdb->prefix}bb_updown_votes";
				$wpdb->query(
					$wpdb->prepare(
						"update `$updown_table` set vote_status =%d where id = %d",
						$vote_status,
						$dislike_id,
					)
				);
			} else {
				$vote_status = 1;
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO $updown_table ( comment_id, activity_id, vote_type,user_id,secondary_item_id,vote_status )
                VALUES ( %d, %d, %s, %d, %d,%d)",
						array(
							$comment_id,
							$activity_id,
							$type,
							$user_id,
							$activity_snd_id,
							$vote_status,
						)
					)
				);
			}
			$updown_table  = "{$wpdb->prefix}bb_updown_votes";
			$dislike_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(%s) from $updown_table where comment_id = %d and activity_id = %d and vote_type = %s and secondary_item_id = %d and vote_status = %d",
					'id',
					$comment_id,
					$activity_id,
					$type,
					$activity_snd_id,
					1
				)
			);
			$response      = array(
				'dislike_count' => $dislike_count,
				'comment_id'    => $comment_id,
			);
			echo wp_json_encode( $response );
			exit;
		}
	}
	/**
	 * Gecount votes.
	 *
	 * @param [type] $comment_id comment id.
	 * @param [type] $activity_id activity id.
	 * @param [type] $type type.
	 * @param [type] $activity_snd_id activity second id.
	 * @return int count.
	 */
	public function acld_bp_get_count( $comment_id, $activity_id, $type, $activity_snd_id ) {
		global $wpdb;
		$updown_table = $wpdb->prefix . 'bb_updown_votes';
		$vote_status  = 1;
		$count        = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(%s) from $updown_table where comment_id = %d and activity_id = %d and vote_type = %s and secondary_item_id = %d and vote_status = %d",
				'id',
				$comment_id,
				$activity_id,
				$type,
				$activity_snd_id,
				$vote_status
			)
		);
		return $count;
	}

}
new ACLD_Vote_UpDown();
