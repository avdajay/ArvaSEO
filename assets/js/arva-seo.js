(function() {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function() {
		var startButton = document.getElementById( 'arva-seo-start-crawl' );
		var stateNode = document.getElementById( 'arva-seo-crawl-state' );
		var progressNode = document.getElementById( 'arva-seo-crawl-progress' );
		var progressPercent = document.getElementById( 'arva-seo-crawl-progress-percent' );
		var progressCopy = document.getElementById( 'arva-seo-crawl-progress-copy' );
		var retryTimer = null;
		var isRunning = false;

		if ( ! startButton || ! stateNode || ! progressNode || ! progressPercent || ! progressCopy || 'undefined' === typeof arvaSeoAdmin ) {
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

			progressCopy.textContent = navigator.onLine ? 'Connection recovered. Retrying crawl shortly...' : 'Connection lost. Waiting to resume crawl...';
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
			}

			function runNextBatch( startFlag ) {
				return crawlBatch( startFlag ).then( function( data ) {
					var summary = data.summary || {};

					setStateMeta( summary );
					setProgress(
						summary.percentage || 0,
						'Processed ' + ( summary.processed || 0 ) + ' of ' + ( summary.total || 0 ) + ' pages. Errors ' + ( summary.error_count || 0 ) + '.'
					);

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
					progressCopy.textContent = error.message;
					progressNode.setAttribute( 'aria-valuenow', stateNode.dataset.percentage || '0' );
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
			progressCopy.textContent = 'Resuming crawl from saved progress...';
			runCrawler( false ).catch( function( error ) {
				progressCopy.textContent = error.message;
				startButton.disabled = false;
			} );
		}
	} );

	document.addEventListener( 'DOMContentLoaded', function() {
		var bulkForm = document.getElementById( 'arva-seo-bulk-edit-form' );
		var bulkButton = document.getElementById( 'arva-seo-start-bulk-edit' );
		var bulkState = document.getElementById( 'arva-seo-bulk-edit-state' );
		var bulkProgress = document.getElementById( 'arva-seo-bulk-edit-progress' );
		var bulkPercent = document.getElementById( 'arva-seo-bulk-edit-progress-percent' );
		var bulkCopy = document.getElementById( 'arva-seo-bulk-edit-progress-copy' );
		var isRunning = false;

		if ( ! bulkForm || ! bulkButton || ! bulkState || ! bulkProgress || ! bulkPercent || ! bulkCopy || 'undefined' === typeof arvaSeoAdmin ) {
			return;
		}

		function setBulkProgress( percentage, copy ) {
			var safePercentage = Math.max( 0, Math.min( 100, percentage ) );

			bulkProgress.style.setProperty( '--progress', safePercentage );
			bulkProgress.setAttribute( 'aria-valuenow', safePercentage );
			bulkPercent.textContent = safePercentage + '%';
			bulkCopy.textContent = copy;
		}

		function serializeRows() {
			var formData = new FormData( bulkForm );
			var rows = {};

			formData.forEach( function( value, key ) {
				var match = key.match( /^rows\[(\d+)\]\[(.+)\]$/ );

				if ( ! match ) {
					return;
				}

				if ( ! rows[ match[1] ] ) {
					rows[ match[1] ] = {};
				}

				rows[ match[1] ][ match[2] ] = value;
			} );

			return Object.keys( rows ).map( function( rowKey ) {
				return rows[ rowKey ];
			} );
		}

		function postBulk( action, payload ) {
			var formData = new FormData();
			formData.append( 'action', action );
			formData.append( 'nonce', arvaSeoAdmin.bulkEditNonce );

			Object.keys( payload ).forEach( function( key ) {
				formData.append( key, payload[ key ] );
			} );

			return window.fetch( arvaSeoAdmin.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} ).then( function( response ) {
				return response.json();
			} ).then( function( payload ) {
				if ( ! payload.success ) {
					throw new Error( payload.data && payload.data.message ? payload.data.message : 'Bulk edit failed.' );
				}

				return payload.data;
			} );
		}

		function runBatchLoop() {
			return postBulk( arvaSeoAdmin.bulkEditProcessAction, {} ).then( function( data ) {
				var state = data.state || {};

				setBulkProgress( state.percentage || 0, data.message || 'Processing...' );

				if ( data.done ) {
					isRunning = false;
					bulkButton.disabled = false;
					return;
				}

				return runBatchLoop();
			} );
		}

		bulkButton.addEventListener( 'click', function() {
			if ( isRunning ) {
				return;
			}

			isRunning = true;
			bulkButton.disabled = true;
			bulkState.scrollIntoView( {
				behavior: 'smooth',
				block: 'start',
			} );
			setBulkProgress( 0, 'Saving preview edits...' );

			postBulk( arvaSeoAdmin.bulkEditPrepareAction, {
				rows: JSON.stringify( serializeRows() ),
			} ).then( function() {
				setBulkProgress( 0, 'Starting bulk update...' );

				return runBatchLoop();
			} ).catch( function( error ) {
				isRunning = false;
				bulkButton.disabled = false;
				bulkCopy.textContent = error.message;
			} );
		} );
	} );
})();
