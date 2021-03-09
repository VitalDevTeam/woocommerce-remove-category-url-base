<?php
defined('ABSPATH') || exit;
/*
	Plugin Name: WooCommerce Remove Product Category URL Base
	Plugin URI: https://vtldesign.com
	Description: Removes the URL base from product category pages.
	Version: 1.0.0
	Requires at least: 5.2
	Requires PHP: 7.0
	Author: Vital
	Author URI: https://vtldesign.com
	Text Domain: wc-rpcub
*/
class WC_Remove_Category_URL_Base {

	public function __construct() {

		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			add_filter('term_link', [$this, 'filter_term_links'], 10, 3);
			add_filter('request', [$this, 'filter_query']);
		}
	}

	/**
	 * Filters the permalink for product category terms.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string $termlink Term link URL.
	 * @param  WP_Term $term Term object.
	 * @param  string $taxonomy Taxonomy slug.
	 * @return string The filtered term link URL.
	 */
	public function filter_term_links($termlink, $term, $taxonomy) {
		$wc_options = get_option('woocommerce_permalinks');

		if (is_array($wc_options) && isset($wc_options['category_base'])) {
			$base = $wc_options['category_base'];
			$termlink = str_replace("/{$base}/", '/', $termlink);
		}

		return $termlink;
	}

	/**
	 * Filters the array of parsed query variables.
	 * Allows us to query product category pages without the URL base.
	 *
	 * Based on original code by Damian Logghe.
	 * https://timersys.com/remove-product-category-slug-woocommerce/
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  array $query_vars The array of requested query variables.
	 * @return array The filtered query variables.
	 */
	public function filter_query($query_vars) {
		global $wpdb;

		if (!empty($query_vars['pagename'])
			|| !empty($query_vars['category_name'])
			|| !empty($query_vars['name'])
			|| !empty($query_vars['attachment'])) {

			$slug = !empty($query_vars['pagename']) ? $query_vars['pagename'] : (!empty($query_vars['name']) ? $query_vars['name'] : (!empty($query_vars['category_name']) ? $query_vars['category_name'] : $query_vars['attachment']));

			$term_exists = $wpdb->get_var($wpdb->prepare("SELECT t.term_id
				FROM $wpdb->terms t
				LEFT JOIN $wpdb->term_taxonomy tt
				ON tt.term_id = t.term_id
				WHERE tt.taxonomy = 'product_cat'
				AND t.slug = %s", [$slug]
			));

			if ($term_exists) {

				$old_vars = $query_vars;
				$query_vars = ['product_cat' => $slug];

				if (!empty($old_vars['paged']) || !empty($old_vars['page'])) {
					$query_vars['paged'] = !empty($old_vars['paged']) ? $old_vars['paged'] : $old_vars['page'];
				}

				if (!empty($old_vars['orderby'])) {
					$query_vars['orderby'] = $old_vars['orderby'];
				}

				if (!empty($old_vars['order'])) {
					$query_vars['order'] = $old_vars['order'];
				}
			}
		}

		return $query_vars;
	}
}

new WC_Remove_Category_URL_Base();
