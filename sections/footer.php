<?php

	wp_print_styles( 'mylisting-footer' );

	$data = c27()->merge_options([
			'footer_background'=> c27()->get_setting('footer_background_color', '#fff'),
			'footer_text'      => c27()->get_setting('footer_text', ''),
			'show_widgets'     => c27()->get_setting('footer_show_widgets', true),
			'show_footer_menu' => c27()->get_setting('footer_show_menu', true),
		], $data);

	if ($data['footer_background']) {
		if (!isset($GLOBALS['case27_custom_styles'])) $GLOBALS['case27_custom_styles'] = '';

		$GLOBALS['case27_custom_styles'] .= 'footer.footer';
		$GLOBALS['case27_custom_styles'] .= '{ background: ' . $data['footer_background'] . ' }';
	}
?>


