<?php

namespace Monei\Templates;

/**
 * The page of the Connect with MONEI callback, a whole HTML document.
 *
 * Colors come from the MONEI Dashboard theme (primary, success, destructive, foreground, muted). Nothing loads from
 * another host: the logo is the plugin's own file and the font is the system font.
 */
class ConnectResult implements TemplateInterface {

	/**
	 * @param array{state: string, title: string, lines: list<array{ok: bool, text: string}>, settings_url: string, logo_url: string} $data
	 *     state is 'connected', 'failed' or 'refused'.
	 */
	public function render( $data ): void {
		$ok = 'connected' === $data['state'];
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $data['title'] ); ?> · MONEI</title>
	<style>
		:root {
			--primary: #65427e;
			--primary-hover: #74558b;
			--primary-foreground: #fafafa;
			--success: #00a96e;
			--success-tint: rgba(0, 169, 110, .1);
			--destructive: #df2225;
			--destructive-tint: rgba(223, 34, 37, .1);
			--foreground: #0a0a0a;
			--muted-foreground: #5e5e5e;
			--background: #f3f3f3;
			--muted: #f5f5f5;
			--line: #e5e5e5;
		}
		*, *::before, *::after { box-sizing: border-box; }
		body {
			margin: 0;
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 24px 16px;
			background: var(--background);
			color: var(--foreground);
			font: 15px/1.5 -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
		}
		.card {
			width: 100%;
			max-width: 440px;
			padding: 40px 32px 32px;
			background: #fff;
			border: 1px solid var(--line);
			border-radius: 20px;
			box-shadow: 0 4px 12px rgba(10, 10, 10, .06);
			text-align: center;
		}
		.logo { display: block; height: 24px; width: auto; margin: 0 auto 32px; }
		.status {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 56px;
			height: 56px;
			margin: 0 auto 16px;
			border-radius: 999px;
			background: var(--success-tint);
			color: var(--success);
		}
		.status--error { background: var(--destructive-tint); color: var(--destructive); }
		h1 { margin: 0 0 20px; color: var(--foreground); font-size: 22px; line-height: 1.3; font-weight: 600; }
		.lines { margin: 0 0 24px; padding: 0; list-style: none; text-align: left; }
		.lines li {
			display: flex;
			gap: 10px;
			padding: 12px 14px;
			border-radius: 14px;
			background: var(--muted);
		}
		.lines li + li { margin-top: 8px; }
		.lines svg { flex: none; margin-top: 3px; }
		.line--ok svg { color: var(--success); }
		.line--problem svg { color: var(--muted-foreground); }
		.button {
			display: inline-block;
			padding: 12px 24px;
			border-radius: 999px;
			background: var(--primary);
			color: var(--primary-foreground);
			font-weight: 600;
			text-decoration: none;
		}
		.button:hover, .button:focus-visible { background: var(--primary-hover); }
		.button:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
		.hint { margin: 16px 0 0; color: var(--muted-foreground); font-size: 13px; }
	</style>
</head>
<body>
	<main class="card" role="status">
		<img class="logo" src="<?php echo esc_url( $data['logo_url'] ); ?>" alt="MONEI" width="110" height="24">
		<div class="status<?php echo $ok ? '' : ' status--error'; ?>" aria-hidden="true">
			<?php if ( $ok ) : ?>
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
			<?php else : ?>
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 16.5v.01"/><circle cx="12" cy="12" r="9"/></svg>
			<?php endif; ?>
		</div>
		<h1><?php echo esc_html( $data['title'] ); ?></h1>
		<?php if ( $data['lines'] ) : ?>
			<ul class="lines">
				<?php foreach ( $data['lines'] as $line ) : ?>
					<li class="<?php echo $line['ok'] ? 'line--ok' : 'line--problem'; ?>">
						<?php if ( $line['ok'] ) : ?>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
						<?php else : ?>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/></svg>
						<?php endif; ?>
						<span><?php echo esc_html( $line['text'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<a class="button" href="<?php echo esc_url( $data['settings_url'] ); ?>"><?php esc_html_e( 'Go to MONEI settings', 'monei' ); ?></a>
		<?php if ( 'refused' !== $data['state'] ) : ?>
			<p class="hint"><?php esc_html_e( 'You can also close this tab and reload the settings page.', 'monei' ); ?></p>
		<?php endif; ?>
	</main>
</body>
</html>
		<?php
	}
}
