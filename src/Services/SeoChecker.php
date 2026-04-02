<?php

namespace ArvaSeo\Services;

class SeoChecker {

	protected array $plugins;
	public string $detected_plugin;

	public function __construct() {
        $this->plugins = [
            // Yoast SEO
            'wordpress-seo/wp-seo.php',
            'wordpress-seo-premium/wp-seo-premium.php',

            // All in One SEO
            'all-in-one-seo-pack/all_in_one_seo_pack.php',
            'all-in-one-seo-pack-pro/all_in_one_seo_pack.php',

            // RankMath
            'seo-by-rank-math/rank-math.php',
            'seo-by-rank-math-pro/rank-math-pro.php',

            // SEOPress
            'wp-seopress/seopress.php',
            'wp-seopress-pro/seopress-pro.php',
        ];
    }

	public function check(): bool {
		foreach ($this->plugins as $plugin)
		{
			if (is_plugin_active($plugin))
			{
				$this->detected_plugin = $plugin;
				return true;
			}
		}

		return false;
	}
}