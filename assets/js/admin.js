/* global LUE */
( function () {
	'use strict';

	var ta = document.getElementById( 'lue-urls' );
	if ( ! ta || typeof LUE === 'undefined' ) {
		return;
	}

	function norm( u ) {
		u = String( u ).trim().toLowerCase().replace( /^https?:\/\/[^\/]+/, '' ).split( /[?#]/ )[ 0 ];
		return '/' + u.replace( /^\/+|\/+$/g, '' );
	}

	function lines() {
		return ta.value.split( /\r?\n/ ).map( function ( s ) {
			return s.trim();
		} ).filter( Boolean );
	}

	function has( path ) {
		var k = norm( path );
		return lines().some( function ( l ) {
			return l.charAt( 0 ) !== '#' && norm( l ) === k;
		} );
	}

	function sync() {
		document.querySelectorAll( '.lue-pick' ).forEach( function ( cb ) {
			cb.checked = has( cb.value );
		} );
	}

	function add( path ) {
		if ( ! has( path ) ) {
			var v = lines();
			v.push( path );
			ta.value = v.join( '\n' );
		}
		sync();
	}

	function remove( path ) {
		var k = norm( path );
		ta.value = lines().filter( function ( l ) {
			return norm( l ) !== k;
		} ).join( '\n' );
		sync();
	}

	document.querySelectorAll( '.lue-pick' ).forEach( function ( cb ) {
		cb.addEventListener( 'change', function () {
			if ( cb.checked ) {
				add( cb.value );
			} else {
				remove( cb.value );
			}
		} );
	} );
	ta.addEventListener( 'input', sync );

	function cell( text, code ) {
		var td = document.createElement( 'td' );
		if ( code ) {
			var c = document.createElement( 'code' );
			c.textContent = text;
			td.appendChild( c );
		} else {
			td.textContent = text;
		}
		return td;
	}

	function message( body, text ) {
		body.innerHTML = '';
		var tr = document.createElement( 'tr' );
		tr.appendChild( cell( text ) );
		body.appendChild( tr );
	}

	function search() {
		var table = document.getElementById( 'lue-results' );
		var body = table.querySelector( 'tbody' );
		var params = new URLSearchParams( {
			action: 'lue_search',
			nonce: LUE.nonce,
			type: document.getElementById( 'lue-type' ).value,
			term: document.getElementById( 'lue-term' ).value
		} );

		fetch( LUE.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				table.hidden = false;
				var items = res && res.success ? res.data : [];
				if ( ! items.length ) {
					message( body, LUE.i18n.none );
					return;
				}
				body.innerHTML = '';
				items.forEach( function ( it ) {
					var tr = document.createElement( 'tr' );
					var action = document.createElement( 'td' );
					var b = document.createElement( 'button' );
					b.type = 'button';
					b.className = 'button button-small';
					b.disabled = has( it.path );
					b.textContent = b.disabled ? LUE.i18n.added : LUE.i18n.exclude;
					b.addEventListener( 'click', function () {
						add( it.path );
						b.textContent = LUE.i18n.added;
						b.disabled = true;
					} );
					action.appendChild( b );
					tr.appendChild( cell( it.title ) );
					tr.appendChild( cell( it.type ) );
					tr.appendChild( cell( it.path, true ) );
					tr.appendChild( action );
					body.appendChild( tr );
				} );
			} )
			.catch( function () {
				table.hidden = false;
				message( body, LUE.i18n.error );
			} );
	}

	document.getElementById( 'lue-search' ).addEventListener( 'click', search );
	document.getElementById( 'lue-term' ).addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Enter' ) {
			e.preventDefault();
			search();
		}
	} );
}() );
