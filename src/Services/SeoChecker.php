<?php

namespace ArvaSeo\Services;

class SeoChecker {

	public function check(): bool {
		return ( new SeoProviderResolver() )->has_active_provider();
	}

	public function get_provider_name(): string {
		return ( new SeoProviderResolver() )->get_active_provider_name();
	}
}
