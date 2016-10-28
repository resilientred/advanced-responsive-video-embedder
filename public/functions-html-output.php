<?php

function arve_get_var_dump( $var ) {
	ob_start();
	var_dump( $var );
	return ob_get_clean();
};

function arve_get_debug_info( $atts, $input_atts ) {

	$html = '';

	if ( isset( $_GET['arve-debug-options'] ) ) {

		static $show_options_debug = true;

		$options               = get_option( 'arve_options_main' );
		$options['shortcodes'] = get_option( 'arve_options_shortcodes' );
		$options['params']     = get_option( 'arve_options_params' );

		if ( $show_options_debug ) {
			$html .= sprintf( 'Options: <pre>%s</pre>', arve_get_var_dump( $options ) );
		}
		$show_options_debug = false;
	}

	if ( ! empty( $_GET['arve-debug-arg'] ) ) {
		$html .= sprintf(
			'<pre>arg[%s]: %s</pre>',
			esc_html( $_GET['arve-debug-arg'] ),
			arve_get_var_dump( $atts[ $_GET['arve-debug-arg'] ] )
		);
	}

	if ( isset( $_GET['arve-debug'] ) ) {
		$html .= sprintf( '<pre>$atts: %s</pre>', arve_get_var_dump( $input_atts ) );
		$html .= sprintf( '<pre>$arve: %s</pre>', arve_get_var_dump( $atts ) );
	}

	return $html;
}

function arve_build_meta_html( $atts ) {

		$meta = '';

		if ( ! empty( $atts['sources'] ) ) {

			$first_source = arve_get_first_array_value( $atts['sources'] );

			$meta .= sprintf( '<meta itemprop="contentURL" content="%s">', esc_attr( $first_source['src'] ) );
		}

		if ( ! empty( $atts['iframe_src'] ) ) {
			$meta .= sprintf( '<meta itemprop="embedURL" content="%s">', esc_attr( $atts['iframe_src'] ) );
		}

		if ( ! empty( $atts['upload_date'] ) ) {
			$meta .= sprintf( '<meta itemprop="uploadDate" content="%s">', esc_attr( $atts['upload_date'] ) );
		}

		if( ! empty( $atts['img_src'] ) ) :

			if( in_array( $atts['mode'], array( 'lazyload', 'lazyload-lightbox' ) ) ) {

				$meta .= sprintf(
					'<img%s>',
					arve_attr( array(
						'class'           => 'arve-thumbnail',
						'data-object-fit' => true,
						'itemprop'        => 'thumbnailUrl',
						'src'             => $atts['img_src'],
						'srcset'          => ! empty( $atts['img_srcset'] ) ? $atts['img_srcset'] : false,
						#'sizes'    => '(max-width: 700px) 100vw, 1280px',
						'alt'             => __( 'Video Thumbnail', 'advanced-responsive-video-embedder' ),
					) )
				);

			} else {

				$meta .= sprintf(
					'<meta%s>',
					arve_attr( array(
						'itemprop' => 'thumbnailUrl',
						'content'  => $atts['img_src'],
					) )
				);
			}

		endif;

		if ( ! empty( $atts['title'] ) && in_array( $atts['mode'], array( 'lazyload', 'lazyload-lightbox' ) ) && empty( $atts['hide_title'] ) ) {
			$meta .= '<h5 itemprop="name" class="arve-title">' . trim( $atts['title'] ) . '</h5>';
		} elseif( ! empty( $atts['title'] ) ) {
			$meta .= sprintf( '<meta itemprop="name" content="%s">', esc_attr( trim( $atts['title'] ) ) );
		}

		if ( ! empty( $atts['description'] ) ) {
			$meta .= '<span itemprop="description" class="arve-description arve-hidden">' . esc_html( trim( $atts['description'] ) ) . '</span>';
		}

		return $meta;
	}

function arve_build_promote_link_html( $arve_link ) {

		if ( $arve_link ) {
			return sprintf(
				'<a href="%s" title="%s" class="arve-promote-link">%s</a>',
				esc_url( 'https://nextgenthemes.com/plugins/advanced-responsive-video-embedder-pro/' ),
				esc_attr( __('Embedded with ARVE Advanced Responsive Video Embedder WordPress plugin', 'advanced-responsive-video-embedder') ),
				esc_html__( 'ARVE', 'advanced-responsive-video-embedder' )
			);
		}

		return '';
	}


function arve_arve_embed_container( $html, $atts ) {

		$attr['class'] = 'arve-embed-container';

		if( ! empty( $atts['aspect_ratio'] ) ) {
			$attr['style'] = sprintf( 'height: 0 !important; padding-bottom: %F%% !important;', arve_aspect_ratio_to_padding( $atts['aspect_ratio'] ) );
		}

		return sprintf( '<div%s>%s</div>', arve_attr( $attr ), $html );
	}

function arve_arve_wrapper( $output, $atts ) {

		$wrapper_class = sprintf(
			'arve-wrapper%s%s%s',
			empty( $atts['hover_effect'] ) ? '' : ' arve-hover-effect-' . $atts['hover_effect'],
			empty( $atts['align'] )        ? '' : ' align' . $atts['align'],
			( 'link-lightbox' == $atts['mode'] ) ? ' arve-hidden' : ''
		);

		$attr = array(
			'id'                   => $atts['embed_id'],
			'class'                => $wrapper_class,
			'data-arve-grow'       => ( 'lazyload' === $atts['mode'] && $atts['grow'] ) ? '' : null,
			'data-arve-mode'       => $atts['mode'],
			'data-arve-provider'   => $atts['provider'],
			'data-arve-webtorrent' => empty( $atts['webtorrent'] ) ? false : $atts['webtorrent'],
			'data-arve-autoplay'   => ( 'webtorrent' == $atts['provider'] && $atts['autoplay'] ) ? true : false,
			'data-arve-controls'   => ( 'webtorrent' == $atts['provider'] && $atts['controls'] ) ? true : false,
			#'data-arve-maxwidth'  => empty( $atts['maxwidth'] ) ? false : sprintf( '%dpx',             $atts['maxwidth'] ),
			'style'                => empty( $atts['maxwidth'] ) ? false : sprintf( 'max-width: %dpx;', $atts['maxwidth'] ),
			// Schema.org
			'itemscope' => '',
			'itemtype'  => 'http://schema.org/VideoObject',
		);

		return sprintf(
			'<div%s>%s</div>',
			arve_attr( $attr ),
			$output
		);
	}

function arve_video_or_iframe( $atts ) {

	if ( 'veoh' == $atts['provider'] ) {

		return arve_create_object( $atts );

	} elseif ( 'html5' == $atts['provider'] ) {

		return arve_create_video_tag( $atts );

	} elseif( 'webtorrent' == $atts['provider'] ) {

		return '<div class="arve-webtorrent-progress-bar"></div>';

	} else {

		return arve_create_iframe_tag( $atts );
	}
}

	/**
	 *
	 *
	 * @since    2.6.0
	 */
function arve_create_iframe_tag( $atts ) {

	$options    = arve_get_options();
	$properties = arve_get_host_properties();

	#d($arve);

	$iframe_attr = array(
		'allowfullscreen' => '',
		'class'       => 'arve-iframe fitvidsignore',
		'frameborder' => '0',
		'name'        => $atts['iframe_name'],
		'sandbox'     => empty( $atts['iframe_sandbox'] ) ? 'allow-scripts allow-same-origin allow-popups' : $atts['iframe_sandbox'],
		'scrolling'   => 'no',
		'src'         => $atts['iframe_src'],
		'height'      => is_feed() ? 480 : false,
		'width'       => is_feed() ? 853 : false,
	);

	if ( ! empty( $properties[ $atts['provider'] ]['requires_flash'] ) ) {
		$iframe_attr['sandbox'] = false;
	}

	if ( in_array( $atts['mode'], array( 'lazyload', 'lazyload-lightbox', 'link-lightbox' ) ) ) {
		$lazyload_iframe_attr = arve_prefix_array_keys( 'data-', $iframe_attr );

		$iframe = sprintf( '<div class="arve-lazyload"%s></div>', arve_attr( $lazyload_iframe_attr ) );
	} else {
		$iframe = sprintf( '<iframe%s></iframe>', arve_attr( $iframe_attr ) );
	}

	return $iframe;
}

function arve_error( $message ) {

	return sprintf(
		'<p><strong>%s</strong> %s</p>',
		__('<abbr title="Advanced Responsive Video Embedder">ARVE</abbr> Error:', ARVE_SLUG ),
		$message
	);
}

function arve_print_styles() {

  $options = arve_get_options();

  if ( (int) $options["video_maxwidth"] > 0 ) {
    $css = sprintf( '.arve-wrapper { max-width: %dpx; }', $options['video_maxwidth'] );

    echo '<style type="text/css">' . $css . "</style>\n";
  }
}

function arve_create_video_tag( $atts ) {

	$soures_html = '';

	if ( in_array( $atts['mode'], array( 'lazyload', 'lazyload-lightbox' ) ) ) {
		$atts['autoplay'] = null;
	}

	$video_attr = array(
		'autoplay' => $atts['autoplay'],
		'class'    => 'arve-video',
		'controls' => $atts['controls'],
		'loop'     => $atts['loop'],
		'poster'   => isset( $atts['img_src'] ) ? $atts['img_src'] : false,
		'preload'  => $atts['preload'],
		'src'      => isset( $atts['video_src'] ) ? $atts['video_src'] : false,

		'width'    => is_feed() ? 853 : false,
		'height'   => is_feed() ? 480 : false,
	);

	if ( isset( $atts['video_sources'] ) ) {

		foreach ( $atts['video_sources'] as $key => $value ) {
			$soures_html .= sprintf( '<source type="%s" src="%s">', $key, $value );
		}
	}

	return sprintf(
		'<video%s>%s%s</video>',
		arve_attr( $video_attr, 'video' ),
		$soures_html,
		$atts['video_tracks']
	);
}

function arve_output_errors( $atts ) {

	$errors = '';

	foreach ( $atts as $key => $value ) {
		if( is_wp_error( $value ) ) {
			$errors .= arve_error( $value->get_error_message() );
		}
	}

	return $errors;
}
