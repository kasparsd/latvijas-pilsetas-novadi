<?php
/*
	Šis ir darbināms tikai ar PHP 5.4+
 */

// Administratīvo teritoriju un apdzīvoto vietu likums:
// http://likumi.lv/doc.php?id=185993
$list = file( 'lv-likums.txt' );

$lv = array();
$lv_pilsetas = array();
$replaced = array();

/*
	Veidojam šādu masīvu:

	array(
		'Novada nosakums' => array(
			'vienibas' => array(
				'Novada pilsēta',
				'Novada apdzīvota vieta'
			)
		),
		...
	);

 */

foreach ( $list as $place ) {

	$parts = explode( '.', trim( $place ) );

	if ( count( $parts ) == 1 ) {
		$lv['pilsetas'][] = trim( $parts[0] );
		$lv_pilsetas['pilsetas'][] = trim( $parts[0] );
	} elseif ( count( $parts ) == 2 ) {
		$lv['novadi'][ (int)$parts[0] ]['novads'] = trim( $parts[1] );
		$lv_pilsetas['novadi'][ (int)$parts[0] ]['novads'] = trim( $parts[1] );
	} elseif ( count( $parts ) == 3 ) {
		if ( stripos( $parts[2], 'pilsēta' ) ) {
			// Pārvēršam "Ķeguma pilsēta" uz "Ķegums", ja vienība satur vārdu pilsēta
			$lv['novadi'][ (int)$parts[0] ][ 'vienibas' ][] = trim( $parts[2] );
			$lv_pilsetas['novadi'][ (int)$parts[0] ][ 'vienibas' ][] = nominativs( $parts[2] );
			$replaced[ $parts[2] ] = nominativs( $parts[2] );
		} else {
			$lv['novadi'][ (int)$parts[0] ][ 'vienibas' ][] = trim( $parts[2] );
			$lv_pilsetas['novadi'][ (int)$parts[0] ][ 'vienibas' ][] = trim( $parts[2] );
		}
	}

}

file_put_contents( 'locisanas-parbaude.json', json_encode( $replaced, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
file_put_contents( 'lv.json', json_encode( $lv, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
file_put_contents( 'lv-pilsetas.json', json_encode( $lv_pilsetas, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );


$simple = array();

foreach ( $lv as $type => $elements ) {
	if ( $type == 'pilsetas' )
		$simple[ 'pilsetas' ] = $elements;
	elseif ( is_array( $elements ) )
		foreach ( $elements as $novads )
			$simple[ $type ][ $novads[ 'novads' ] ] = (array) $novads[ 'vienibas' ];
}

file_put_contents( 'lv-simple.json', json_encode( $simple, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );


$simple = array();

foreach ( $lv_pilsetas as $type => $elements ) {
	if ( $type == 'pilsetas' )
		$simple[ 'pilsetas' ] = $elements;
	elseif ( is_array( $elements ) )
		foreach ( $elements as $novads )
			$simple[ $type ][ $novads[ 'novads' ] ] = (array) $novads[ 'vienibas' ];
}

file_put_contents( 'lv-pilsetas-simple.json', json_encode( $simple, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );


/*
	Veidojam šādu masīvu:

	array(
		'Vienība A pilsēta' => 'Vienības novads',
		'Vienība B pilsēta' => 'Vienības novads',
		...
	);

	Paredzēts, lai būtu viegli atrast vienības novadu.

 */

$out = array();

foreach ( $lv as $type => $elements )
	foreach ( $elements as $i => $element )
		if ( isset( $element['vienibas'] ) )
			foreach ( $element['vienibas'] as $child )
				$out[ $child ] = $element['novads'];
		elseif ( is_string( $element ) )
			$out[ $element ] = $element;

file_put_contents( 'lv-vieniba-novads.json', json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );


/*
	Veidojam šādu masīvu, kur visas vienības "Ķeguma pilsēta" ir aizstātas ar "Ķegums".

	array(
		'Vienība A' => 'Vienības novads',
		'Vienība B' => 'Vienības novads',
		...
	);

	Paredzēts, lai būtu viegli atrast vienības (lietotu tautas valodā) novadu.
 */

$out = array();

foreach ( $lv_pilsetas as $type => $elements )
	foreach ( $elements as $i => $element )
		if ( isset( $element['vienibas'] ) )
			foreach ( $element['vienibas'] as $child )
				$out[ $child ] = $element['novads'];
		elseif ( is_string( $element ) )
			$out[ $element ] = $element;

file_put_contents( 'lv-vieniba-pilseta-novads.json', json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );


/**
 * Primitīvs veids, kā atrast pilsētas nosaukuma nominatīvu (Ķegums)
 * no tās ģenetīva (Ķeguma pilsēta).
 */
function nominativs( $genetivs ) {

	$genetivs = trim( str_replace( 'pilsēta', '', $genetivs ) );

	$map = array(
		'lsu' => 'lsi', // Talsu pilsēta > Talsi
		'su' => 'sis', // Cēsu pilsēta > Cēsis
		'as' => 'a',
		'es' => 'e',
		'a' => 's',
		'u' => 'i',
	);

	foreach ( $map as $end => $replace )
		if ( substr( $genetivs, - strlen( $end ) ) == $end )
			return substr_replace( $genetivs, $replace, - strlen( $end ) );

	return $genetivs;

}

