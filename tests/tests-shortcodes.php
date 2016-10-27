<?php

class Tests_Shortcode extends WP_UnitTestCase {



	public function test_thumbnails() {

		$filename = dirname( __FILE__ ) . '/test-attachment.jpg';
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( basename( $filename ), null, $contents );
		$this->assertTrue( empty( $upload['error'] ) );

		$attachment_id = parent::_make_attachment( $upload );

		$attr = array(
			'url'       => 'https://www.youtube.com/watch?v=hRonZ4wP8Ys',
			'thumbnail' => (string) $attachment_id,
			'title'     => 'Something',
		);

		$this->assertRegExp( '#<meta itemprop="thumbnailUrl" content=".*test-attachment\.jpg#', arve_shortcode_arve( $attr ) );

		$attr['thumbnail'] = 'https://example.com/image.jpg';
		$this->assertContains( '<meta itemprop="thumbnailUrl" content="https://example.com/image.jpg"', arve_shortcode_arve( $attr ) );
	}

	public function test_shortcodes_are_registered() {
		global $shortcode_tags;

		$this->assertArrayHasKey( 'arve', $shortcode_tags );
		$this->assertArrayHasKey( 'youtube', $shortcode_tags );
		$this->assertArrayHasKey( 'vimeo', $shortcode_tags );
	}

	public function test_compare_shortcodes() {

		$atts = array(
			'id'        => 'hRonZ4wP8Ys',
			'provider'  => 'youtube',
			'thumbnail' => 'https://example.com/image.jpg',
			'title'     => 'Something',
			'url'       => 'https://www.youtube.com/watch?v=hRonZ4wP8Ys',
		);

		$new_atts = $old_atts = $atts;

		$this->assertEquals(
			arve_shortcode_arve( $old_atts, null, false ),
			arve_shortcode_arve( $new_atts )
		);

		unset( $old_atts['url'] );

		unset( $new_atts['id'] );
		unset( $new_atts['provider'] );

		$this->assertEquals(
			arve_shortcode_arve( $old_atts, null, false ),
			arve_shortcode_arve( $new_atts )
		);
	}

	public function NO_test_modes() {

		$output = arve_shortcode_arve( array( 'url' => 'https://www.youtube.com/watch?v=hRonZ4wP8Ys' ) );

		$this->assertNotContains( 'Error', $output );
		$this->assertContains( 'data-arve-mode="normal"', $output );

		$modes = array( 'lazyload', 'lazyload-lightbox' );

		foreach ( $modes as $key => $mode ) {

			$output = arve_shortcode_arve( array( 'url' => 'https://www.youtube.com/watch?v=hRonZ4wP8Ys', 'mode' => $mode ) );
			$this->assertContains( 'Error', $output );
		}
	}

	public function test_attr() {

		$atts = array(
			'align'       => 'left',
			'autoplay'    => 'y',
			'description' => '    Description Test   ',
			'maxwidth'    => '333',
			'thumbnail'   => 'https://example.com/image.jpg',
			'title'       => ' Test <title>  ',
			'upload_date' => '2016-10-22',
			'url'         => 'https://www.youtube.com/watch?v=hRonZ4wP8Ys',
		);

		$output = arve_shortcode_arve( $atts );

		$this->assertNotContains( 'Error', $output );

		$this->assertContains( 'alignleft', $output );
		$this->assertContains( 'autoplay=1', $output );
		$this->assertContains( '<span itemprop="description" class="arve-description arve-hidden">Description Test</span>', $output );
		$this->assertContains( 'style="max-width: 333px;"', $output );
		$this->assertContains( '<meta itemprop="name" content="Test &lt;title&gt;">', $output );
		$this->assertContains( '<meta itemprop="uploadDate" content="2016-10-22">', $output );
		$this->assertContains( 'src="https://www.youtube-nocookie.com/embed/hRonZ4wP8Ys', $output );
	}

	public function test_html5() {

		$html5_ext = array( 'mp4', 'm4v', 'webm', 'ogv' );

		foreach ( $html5_ext as $ext ) {

			$output = arve_shortcode_arve( array( 'url' => 'https://example.com/video.' . $ext ) );

			$this->assertNotContains( 'Error', $output );
			$this->assertNotContains( '<iframe', $output );
			$this->assertContains( 'data-arve-provider="html5"', $output );
			$this->assertContains( '<video', $output );

			$output = arve_shortcode_arve( array( $ext => 'https://example.com/video.' . $ext ) );

			$this->assertNotContains( 'Error', $output );
			$this->assertNotContains( '<iframe', $output );
			$this->assertContains( 'data-arve-provider="html5"', $output );
			$this->assertContains( '<video', $output );
		}

		$output = arve_shortcode_arve( array(
			'mp4'       => 'https://example.com/video.mp4',
			'ogv'       => 'https://example.com/video.ogv',
			'webm'      => 'https://example.com/video.webm',
			'thumbnail' => 'https://example.com/image.jpg',
		) );

		$this->assertNotContains( 'Error', $output );
		$this->assertNotContains( '<iframe', $output );
		$this->assertContains( 'data-arve-provider="html5"', $output );
		$this->assertContains( '<video', $output );
		$this->assertContains( 'poster="https://example.com/image.jpg"', $output );
		$this->assertContains( '<source type="video/ogg" src="https://example.com/video.ogv">', $output );
		$this->assertContains( '<source type="video/mp4" src="https://example.com/video.mp4">', $output );
		$this->assertContains( '<source type="video/webm" src="https://example.com/video.webm">', $output );
	}

	public function test_iframe() {

		$output = arve_shortcode_arve( array( 'url' => 'https://example.com' ) );

		$this->assertNotContains( 'Error', $output );
		$this->assertRegExp( '#<iframe .*src="https://example\.com#', $output );
		$this->assertContains( 'data-arve-provider="iframe"', $output );
	}

	public function test_regex() {

		$properties = arve_get_host_properties();

		foreach( $properties as $provider => $props ) :

	    if ( empty( $props['regex'] ) || empty( $props['tests'] ) ) {
	      continue;
	    }

	    foreach( $props['tests'] as $test ) {

				$this->assertTrue( is_array( $test ), $provider );
				$this->assertArrayHasKey( 'id',  $test, $provider );
				$this->assertArrayHasKey( 'url', $test, $provider );

	      preg_match( '#' . $props['regex'] . '#i', $test['url'], $matches );

				$this->assertArrayHasKey( 1, $matches, $provider );
	      $this->assertEquals( $matches[1], $test['id'], $provider );
	    }

	  endforeach;
	}

}
