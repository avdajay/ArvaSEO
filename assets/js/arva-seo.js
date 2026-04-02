(function() {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function() {
		var startButton = document.getElementById( 'arva-seo-start-crawl' );
		var statusNode = document.getElementById( 'arva-seo-crawl-status' );
		var stateNode = document.getElementById( 'arva-seo-crawl-state' );
		var progressNode = document.getElementById( 'arva-seo-crawl-progress' );
		var progressPercent = document.getElementById( 'arva-seo-crawl-progress-percent' );
		var progressCopy = document.getElementById( 'arva-seo-crawl-progress-copy' );
		var retryTimer = null;
		var isRunning = false;

		if ( ! startButton || ! statusNode || ! stateNode || ! progressNode || ! progressPercent || ! progressCopy || 'undefined' === typeof arvaSeoAdmin ) {
			return;
		}

		function setProgress( percentage, copy ) {
			var safePercentage = Math.max( 0, Math.min( 100, percentage ) );

			progressNode.style.setProperty( '--progress', safePercentage );
			progressNode.setAttribute( 'aria-valuenow', safePercentage );
			progressPercent.textContent = safePercentage + '%';
			progressCopy.textContent = copy;
		}

		function setStateMeta( summary ) {
			stateNode.dataset.active = summary.done ? '0' : '1';
			stateNode.dataset.percentage = summary.percentage || 0;
			stateNode.dataset.processed = summary.processed || 0;
			stateNode.dataset.total = summary.total || 0;
			stateNode.dataset.status = summary.status || 'idle';
			stateNode.dataset.crawled = summary.crawled_count || 0;
			stateNode.dataset.skipped = summary.skipped_count || 0;
			stateNode.dataset.errors = summary.error_count || 0;
		}

		function scheduleRetry( shouldStart ) {
			if ( retryTimer ) {
				return;
			}

			statusNode.textContent = navigator.onLine ? 'Connection recovered. Retrying crawl shortly...' : 'Connection lost. Waiting to resume crawl...';
			retryTimer = window.setTimeout( function() {
				retryTimer = null;
				runCrawler( shouldStart );
			}, 3000 );
		}

		function crawlBatch( shouldStart ) {
			var formData = new FormData();
			formData.append( 'action', arvaSeoAdmin.crawlAction );
			formData.append( 'nonce', arvaSeoAdmin.crawlNonce );
			formData.append( 'limit', arvaSeoAdmin.crawlChunkSize || 20 );
			formData.append( 'start', shouldStart ? '1' : '0' );

			return window
				.fetch( arvaSeoAdmin.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData,
				} )
				.then( function( response ) {
					return response.json();
				} )
				.then( function( payload ) {
					if ( ! payload.success ) {
						throw new Error( payload.data && payload.data.message ? payload.data.message : 'The crawl failed.' );
					}

					return payload.data;
				} );
		}

		function applyPersistedState() {
			var percentage = parseInt( stateNode.dataset.percentage || '0', 10 );
			var processed = parseInt( stateNode.dataset.processed || '0', 10 );
			var total = parseInt( stateNode.dataset.total || '0', 10 );

			setProgress(
				percentage,
				total > 0 ? 'Processed ' + processed + ' of ' + total + ' pages.' : 'Waiting to start.'
			);
		}

		function runCrawler( shouldStart ) {
			if ( isRunning ) {
				return Promise.resolve();
			}

			isRunning = true;

			if ( shouldStart ) {
				setProgress( 0, 'Preparing first batch...' );
				statusNode.textContent = 'Starting crawl...';
			}

			function runNextBatch( startFlag ) {
				return crawlBatch( startFlag ).then( function( data ) {
					var summary = data.summary || {};

					setStateMeta( summary );
					setProgress(
						summary.percentage || 0,
						'Processed ' + ( summary.processed || 0 ) + ' of ' + ( summary.total || 0 ) + ' pages. Skipped ' + ( summary.skipped_count || 0 ) + ', errors ' + ( summary.error_count || 0 ) + '.'
					);
					statusNode.textContent = data.message;

					if ( summary.done ) {
						setProgress( 100, 'Crawl complete.' );
						isRunning = false;
						window.location.reload();
						return;
					}

					return runNextBatch( false );
				} ).catch( function( error ) {
					isRunning = false;

					if ( ! navigator.onLine || 'Failed to fetch' === error.message ) {
						scheduleRetry( false );
						return;
					}

					throw error;
				} );
			}

			return runNextBatch( shouldStart );
		}

		startButton.addEventListener( 'click', function() {
			if ( startButton.disabled ) {
				return;
			}

			startButton.disabled = true;
			runCrawler( true )
				.catch( function( error ) {
					statusNode.textContent = error.message;
					applyPersistedState();
					startButton.disabled = false;
				} );
		} );

		window.addEventListener( 'online', function() {
			if ( '1' !== stateNode.dataset.active ) {
				return;
			}

			startButton.disabled = true;
			scheduleRetry( false );
		} );

		applyPersistedState();

		if ( '1' === stateNode.dataset.active ) {
			startButton.disabled = true;
			statusNode.textContent = 'Resuming crawl from saved progress...';
			runCrawler( false ).catch( function( error ) {
				statusNode.textContent = error.message;
				startButton.disabled = false;
			} );
		}
	} );
})();
