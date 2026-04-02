<?php

namespace ArvaSeo\Services;

use ArvaSeo\Contracts\SeoService;

class Yoast implements SeoService {

	public function crawl() {
		// TODO: Implement crawl() method.
	}

	public function get_post_id(string $url): int
	{
		return url_to_postid($url);
	}

	public function get_term_id( string $url): int
	{
		$path     = parse_url($url, PHP_URL_PATH);
		$path     = ltrim($path, '/');
		$path     = rtrim($path, '/');
		$path     = explode('/', $path);
		$term     = end($path);
		$term_id  = ($path[0] == 'product-category') ? term_exists($term, 'product_cat') : term_exists($term);

		return ($path[0] == 'product-category') ? $term_id['term_id'] : $term_id;
	}

	public function get_term_taxonomy(int $id): string
	{
		$term = get_term($id);

		return $term->taxonomy;
	}

	public function is_post_or_term(string $url): string
	{
		$type    = 'term';
		$post_id = $this->get_post_id($url);

		if($post_id > 0) {
			$type = 'post';
		}

		return $type;
	}

}