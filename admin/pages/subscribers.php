<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap af-wrap">

	<h1 class="wp-heading-inline">Subscribers</h1>
	<a
		href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=af_export_subscribers' ), 'af_export_subscribers' ) ); ?>"
		class="page-title-action"
	>Export CSV</a>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Subscriber deleted.</p></div>
	<?php endif; ?>

	<?php
	$active_count = AF_Email_Subscribers::count( 'active' );
	$total_count  = AF_Email_Subscribers::count( '' );
	$search       = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
	$paged        = max( 1, absint( $_GET['paged'] ?? 1 ) );
	$per_page     = 50;
	$offset       = ( $paged - 1 ) * $per_page;

	$subscribers = AF_Email_Subscribers::get_all( array(
		'search' => $search,
		'status' => '',
		'limit'  => $per_page,
		'offset' => $offset,
	) );
	?>

	<div class="af-stats">
		<div class="af-stat-box">
			<span class="af-stat-number"><?php echo number_format( $active_count ); ?></span>
			<span class="af-stat-label">Active</span>
		</div>
		<div class="af-stat-box">
			<span class="af-stat-number"><?php echo number_format( $total_count ); ?></span>
			<span class="af-stat-label">Total</span>
		</div>
	</div>

	<form method="get">
		<input type="hidden" name="page" value="af-email">
		<p class="search-box">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search email or name...">
			<button type="submit" class="button">Search</button>
			<?php if ( $search ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=af-email' ) ); ?>" class="button">Clear</a>
			<?php endif; ?>
		</p>
	</form>

	<table class="wp-list-table widefat fixed striped af-table">
		<thead>
			<tr>
				<th>Email</th>
				<th>First Name</th>
				<th>Status</th>
				<th>Subscribed</th>
				<th style="width:80px">Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $subscribers ) : ?>
				<?php foreach ( $subscribers as $sub ) : ?>
				<tr>
					<td><?php echo esc_html( $sub->email ); ?></td>
					<td><?php echo esc_html( $sub->first_name ); ?></td>
					<td>
						<span class="af-status af-status-<?php echo esc_attr( $sub->status ); ?>">
							<?php echo esc_html( $sub->status ); ?>
						</span>
					</td>
					<td><?php echo esc_html( date( 'M j, Y', strtotime( $sub->subscribed_at ) ) ); ?></td>
					<td>
						<form
							method="post"
							action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
							onsubmit="return confirm('Delete <?php echo esc_js( $sub->email ); ?>?')"
						>
							<input type="hidden" name="action"        value="af_delete_subscriber">
							<input type="hidden" name="subscriber_id" value="<?php echo absint( $sub->id ); ?>">
							<?php wp_nonce_field( 'af_delete_subscriber' ); ?>
							<button type="submit" class="button button-small af-btn-delete">Delete</button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td colspan="5" style="text-align:center;padding:30px;">
					<?php echo $search ? 'No results for "' . esc_html( $search ) . '".' : 'No subscribers yet. Add <code>[subscribe_form]</code> to a post or page to get started.'; ?>
				</td></tr>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $total_count > $per_page ) : ?>
		<div class="af-pagination">
			<?php
			$pages = ceil( $total_count / $per_page );
			for ( $i = 1; $i <= $pages; $i++ ) {
				$url = add_query_arg( array( 'page' => 'af-email', 'paged' => $i, 's' => $search ), admin_url( 'admin.php' ) );
				echo '<a href="' . esc_url( $url ) . '" class="button' . ( $i === $paged ? ' button-primary' : '' ) . '">' . $i . '</a> ';
			}
			?>
		</div>
	<?php endif; ?>

</div>
