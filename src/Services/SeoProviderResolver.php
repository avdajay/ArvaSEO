<?php

namespace ArvaSeo\Services;

use ArvaSeo\Contracts\SeoService;

class SeoProviderResolver {

	/**
	 * @var SeoService[]
	 */
	private array $providers;

	/**
	 * @param SeoService[]|null $providers
	 */
	public function __construct( ?array $providers = null ) {
		$this->providers = $providers ?? [
			new Yoast(),
			new AllInOneSeo(),
			new RankMath(),
			new SEOPress(),
		];
	}

	public function resolve(): SeoService {
		foreach ( $this->providers as $provider ) {
			if ( $provider->is_active() ) {
				return $provider;
			}
		}

		return new NullSeoService();
	}

	public function has_active_provider(): bool {
		return $this->resolve()->is_active();
	}

	public function get_active_provider_name(): string {
		return $this->resolve()->get_provider_name();
	}
}
