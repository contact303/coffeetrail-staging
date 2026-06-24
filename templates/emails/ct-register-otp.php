<?php
/**
 * CoffeeTrail registration OTP email.
 *
 * Expected variables:
 * @var string $otp_code
 * @var string $otp_email
 *
 * Install at:
 * wp-content/themes/my-listing-child/templates/emails/ct-register-otp.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
$logo_url  = '';
$logo_id   = get_theme_mod( 'custom_logo' );

if ( $logo_id ) {
	$logo_url = wp_get_attachment_image_url( $logo_id, 'medium' );
}

if ( ! $logo_url && function_exists( 'get_site_icon_url' ) ) {
	$logo_url = get_site_icon_url( 192 );
}
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $site_name ); ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f6f5;font-family:Arial,Helvetica,sans-serif;direction:rtl;text-align:right;color:#101828;">
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f6f5;padding:32px 12px;">
		<tr>
			<td align="center">
				<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:580px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 18px rgba(16,24,40,.08);">
					<tr>
						<td style="height:8px;background:#00a63e;font-size:0;line-height:0;">&nbsp;</td>
					</tr>
					<tr>
						<td align="center" style="padding:34px 32px 18px;">
							<?php if ( $logo_url ) : ?>
								<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" style="display:block;max-width:180px;max-height:72px;width:auto;height:auto;border:0;">
							<?php else : ?>
								<div style="font-size:24px;font-weight:700;color:#00a63e;"><?php echo esc_html( $site_name ); ?></div>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td style="padding:8px 40px 36px;text-align:center;">
							<h1 style="margin:0 0 14px;font-size:26px;line-height:1.35;color:#101828;">אימות כתובת האימייל</h1>
							<p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#667085;">השתמשו בקוד הבא כדי להשלים את יצירת החשבון שלכם:</p>

							<div dir="ltr" style="display:inline-block;background:#f2f4f7;border:1px solid #e4e7ec;border-radius:12px;padding:18px 28px;margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;font-size:34px;line-height:1;font-weight:700;letter-spacing:9px;color:#101828;">
								<?php echo esc_html( $otp_code ); ?>
							</div>

							<p style="margin:0 0 8px;font-size:14px;line-height:1.7;color:#667085;">הקוד תקף למשך 10 דקות.</p>
							<p style="margin:0;font-size:13px;line-height:1.7;color:#98a2b3;">אם לא ביקשתם ליצור חשבון, אפשר להתעלם מהודעה זו.</p>
						</td>
					</tr>
					<tr>
						<td style="padding:20px 32px;background:#f9fafb;border-top:1px solid #eaecf0;text-align:center;font-size:12px;line-height:1.6;color:#98a2b3;">
							<?php echo esc_html( $site_name ); ?> — כל עגלות הקפה במקום אחד
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
