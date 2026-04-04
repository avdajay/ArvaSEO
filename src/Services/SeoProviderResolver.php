<?php

namespace ArvaSeo\Services;

use ArvaSeo\Contracts\SeoService;

class SeoProviderResolver {

	/**
	 * @var SeoService[]
	 */
	private array $providers;
	private Licensing $licensing;

	/**
	 * @param SeoService[]|null $providers
	 */
	public function __construct( ?array $providers = null, ?Licensing $licensing = null ) {
		$this->providers = $providers ?? [
			new Yoast(),
		];
		$this->licensing = $licensing ?? new Licensing();

		if ( function_exists( 'arva_seo_fs' ) && arva_seo_fs()->is__premium_only() ) {
			$this->register_premium_providers__premium_only();
		}
	}

	private function register_premium_providers__premium_only(): void {
		$this->providers[] = new AllInOneSeo();
		$this->providers[] = new RankMath();
		$this->providers[] = new SEOPress();
	}

	public function resolve(): SeoService {
		foreach ( $this->providers as $provider ) {
			if ( $provider->is_active() && $this->licensing->can_use_provider( $provider ) ) {
				return $provider;
			}
		}

		return new NullSeoService();
	}

	public function get_detected_provider(): SeoService {
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

	public function get_detected_provider_name(): string {
		return $this->get_detected_provider()->get_provider_name();
	}

	public function detected_provider_requires_premium(): bool {
		return $this->licensing->provider_requires_premium( $this->get_detected_provider() );
	}
}
