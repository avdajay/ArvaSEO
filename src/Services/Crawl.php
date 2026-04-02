<?php

namespace ArvaSeo\Services;

use ArvaSeo\Contracts\SeoService;

class Crawl {
	private int $page_id;
	private string $page_title;
	private string $page_url;
	private int $page_score;
	private string $post_term;
	private string $post_taxonomy;
	private string $post_type;
	private SeoService $seo_service;

	public function __construct(SeoService $seo_service) {
		$this->seo_service = $seo_service;
	}

}