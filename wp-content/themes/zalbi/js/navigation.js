/**
 * File navigation.js.
 * Controla la apertura y cierre del menú en móvil y el cambio de icono.
 */
( function() {
	// Buscamos el contenedor del menú y el botón por sus IDs y Clases nuevas
	const siteNavigation = document.getElementById( 'site-navigation' );
	const button = document.querySelector( '.menu-toggle' );

	// Si no encuentra alguno de los dos, no hace nada (evita errores)
	if ( ! siteNavigation || ! button ) {
		return;
	}

	// Al hacer click en el botón...
	button.addEventListener( 'click', function() {
		// 1. Añade o quita la clase .toggled al menú (esto lo muestra/oculta por CSS)
		siteNavigation.classList.toggle( 'toggled' );

		// 2. Lógica para cambiar el icono (de Barras a X)
		const icon = button.querySelector('i');
		if ( icon ) {
			if ( siteNavigation.classList.contains( 'toggled' ) ) {
				icon.classList.remove( 'fa-bars' );
				icon.classList.add( 'fa-times' );
			} else {
				icon.classList.remove( 'fa-times' );
				icon.classList.add( 'fa-bars' );
			}
		}

		// 3. Accesibilidad: avisa a los lectores de pantalla si está abierto
		const expanded = siteNavigation.classList.contains( 'toggled' ) ? 'true' : 'false';
		button.setAttribute( 'aria-expanded', expanded );
	} );

	// EXTRA: Cerrar el menú si haces click fuera de él (Mejora mucho la experiencia)
	document.addEventListener( 'click', function( event ) {
		const isClickInside = siteNavigation.contains( event.target ) || button.contains( event.target );

		if ( ! isClickInside && siteNavigation.classList.contains( 'toggled' ) ) {
			siteNavigation.classList.remove( 'toggled' );
			button.setAttribute( 'aria-expanded', 'false' );
			
			// Reseteamos el icono a barras
			const icon = button.querySelector('i');
			if ( icon ) {
				icon.classList.remove( 'fa-times' );
				icon.classList.add( 'fa-bars' );
			}
		}
	} );

}() );