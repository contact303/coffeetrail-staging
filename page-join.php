<?php
/**
 * Template Name: עמוד הצטרפות
 * Template Post Type: page
 */

defined('ABSPATH') || exit;

$theme_uri = get_stylesheet_directory_uri();
$page_id   = get_queried_object_id();

$css_path = get_stylesheet_directory() . '/assets/css/page-join.css';
$js_path  = get_stylesheet_directory() . '/assets/js/page-join.js';

wp_enqueue_style(
    'coffeetrail-join-page',
    get_stylesheet_directory_uri() . '/assets/css/page-join.css',
    [],
    file_exists($css_path) ? filemtime($css_path) : null
);

wp_enqueue_script(
    'coffeetrail-join-page',
    get_stylesheet_directory_uri() . '/assets/js/page-join.js',
    [],
    file_exists($js_path) ? filemtime($js_path) : null,
    true
);

get_header();

$button = static function ($field, $class = 'ct-join__button') use ($page_id) {
    $link = get_field($field, $page_id);

    if (is_string($link) && $link !== '') {
        printf(
            '<a class="%1$s" href="%2$s">%3$s</a>',
            esc_attr($class),
            esc_url($link),
            esc_html__('למידע נוסף', 'coffeetrail')
        );
        return;
    }

    if (!is_array($link) || empty($link['url']) || empty($link['title'])) {
        return;
    }

    printf(
        '<a class="%1$s" href="%2$s" target="%3$s">%4$s</a>',
        esc_attr($class),
        esc_url($link['url']),
        esc_attr(!empty($link['target']) ? $link['target'] : '_self'),
        esc_html($link['title'])
    );
};

$image = static function ($field, $size = 'full', $class = '') use ($page_id) {
    $image_id = get_field($field, $page_id);

    if (!$image_id) {
        return;
    }

    echo wp_get_attachment_image(
        (int) $image_id,
        $size,
        false,
        [
            'class'   => esc_attr($class),
            'loading' => 'lazy',
        ]
    );
};
?>

<main class="ct-join" dir="rtl">

    <section class="ct-join__hero">
        <div class="ct-join__container">
            <div class="ct-join__hero-grid">
                <div class="ct-join__hero-content">
                    <?php if ($eyebrow = get_field('join_hero_eyebrow', $page_id)) : ?>
                        <div class="ct-join__eyebrow"><?php echo esc_html($eyebrow); ?></div>
                    <?php endif; ?>

                    <?php if ($title = get_field('join_hero_title', $page_id)) : ?>
                        <h1><?php echo wp_kses_post($title); ?></h1>
                    <?php endif; ?>

                    <?php if ($text = get_field('join_hero_text', $page_id)) : ?>
                        <div class="ct-join__lead"><?php echo wp_kses_post($text); ?></div>
                    <?php endif; ?>

                    <?php $button('join_hero_button'); ?>

                    <?php if ($note = get_field('join_hero_note', $page_id)) : ?>
                        <div class="ct-join__note"><?php echo esc_html($note); ?></div>
                    <?php endif; ?>

                    <?php if ($social = get_field('join_hero_social_proof', $page_id)) : ?>
                        <div class="ct-join__social-proof">
                            <?php echo esc_html($social); ?>
                            <div data-dc-tpl="92" style="display: flex; flex-direction: row-reverse; align-items: center;">
                                <span data-dc-tpl="93" style="width: 30px; height: 30px; border-radius: 50%; background: rgb(110, 123, 133); border: 2px solid rgb(251, 250, 244);"></span>
                                <span data-dc-tpl="94" style="width: 30px; height: 30px; border-radius: 50%; background: rgb(138, 147, 155); border: 2px solid rgb(251, 250, 244); margin-right: -10px;"></span>
                                <span data-dc-tpl="95" style="width: 30px; height: 30px; border-radius: 50%; background: rgb(198, 204, 209); border: 2px solid rgb(251, 250, 244); margin-right: -10px;"></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="ct-join__hero-media">
                    <?php $image('join_hero_image', 'full', 'ct-join__hero-image'); ?>
                    <div class="ct-join__hero-content-media">       
                        <?php if ($badge = get_field('join_hero_image_badge', $page_id)) : ?>
                            <div class="ct-join__image-badge"><?php echo esc_html($badge); ?></div>
                        <?php endif; ?>
                        <?php if ($number = get_field('join_hero_image_number', $page_id)) : ?>
                            <div
                                class="ct-join__image-number"
                                data-counter
                                data-value="<?php echo esc_attr($number); ?>"
                                data-prefix="<?php echo esc_attr($prefix ?? ''); ?>"
                                data-suffix="<?php echo esc_attr($suffix ?? '+'); ?>"
                                data-duration="<?php echo esc_attr($duration ?? 1600); ?>"
                            >
                                <?php echo esc_html(($prefix ?? '') . '0' . ($suffix ?? '+')); ?>
                            </div>
                        <?php endif; ?>    
                        <?php if ($badge_bottom = get_field('join_hero_image_badge_bottom', $page_id)) : ?>
                            <div class="ct-join__image-badge-bottom"><?php echo esc_html($badge_bottom); ?></div>
                        <?php endif; ?>  
                    </div>                                    
                </div>
            </div>

            <?php if (have_rows('join_hero_stats', $page_id)) : ?>
                <div class="ct-join__stats">
                    <?php while (have_rows('join_hero_stats', $page_id)) : the_row(); ?>
                        <?php
                        $title = get_sub_field('title');
                        $text  = get_sub_field('text');
                        ?>

                        <div class="ct-join__stat">
                            <?php if ($title) : ?>
                                <div class="ct-join__stat-title">
                                    <?php echo esc_html($title); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($text) : ?>
                                <div class="ct-join__stat-label">
                                    <?php echo esc_html($text); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="ct-join__section ct-join__how" id="how">
        <div class="ct-join__container ct-join__center">
            <?php if ($eyebrow = get_field('join_how_eyebrow', $page_id)) : ?>
                <div class="ct-join__eyebrow"><?php echo esc_html($eyebrow); ?></div>
            <?php endif; ?>

            <?php if ($title = get_field('join_how_title', $page_id)) : ?>
                <h2><?php echo wp_kses_post($title); ?></h2>
            <?php endif; ?>

            <?php if ($text = get_field('join_how_text', $page_id)) : ?>
                <div class="ct-join__section-text"><?php echo wp_kses_post($text); ?></div>
            <?php endif; ?>

            <?php if (have_rows('join_how_steps', $page_id)) : ?>
                <div class="ct-join__steps">
                    <?php while (have_rows('join_how_steps', $page_id)) : the_row(); ?>
                        <article class="ct-join__step">
                            <div class="ct-join__step-number"><?php echo esc_html(get_sub_field('number')); ?></div>
                            <div class="ct-join__step-content">
                                <h3><?php echo esc_html(get_sub_field('title')); ?></h3>
                                <p><?php echo esc_html(get_sub_field('text')); ?></p>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="ct-join__section ct-join__advantage">
        <div class="ct-join__container ct-join__advantage-grid">
            <div>
                <?php if ($eyebrow = get_field('join_advantage_eyebrow', $page_id)) : ?>
                    <div class="ct-join__eyebrow ct-join__eyebrow--light"><?php echo esc_html($eyebrow); ?></div>
                <?php endif; ?>

                <?php if ($title = get_field('join_advantage_title', $page_id)) : ?>
                    <h2><?php echo wp_kses_post($title); ?></h2>
                <?php endif; ?>

                <?php if ($text = get_field('join_advantage_text', $page_id)) : ?>
                    <div class="ct-join__section-text"><?php echo wp_kses_post($text); ?></div>
                <?php endif; ?>

                <?php if ($filter_title = get_field('join_advantage_filter_title', $page_id)) : ?>
                    <h3 class="ct-join__filter-title"><?php echo esc_html($filter_title); ?></h3>
                <?php endif; ?>

                <?php if (have_rows('join_advantage_filters', $page_id)) : ?>
                    <div class="ct-join__filters">
                        <?php while (have_rows('join_advantage_filters', $page_id)) : the_row(); ?>
                            <div class="ct-join__filter">
                                <?php
                                $icon = get_sub_field('icon');
                                if ($icon) {
                                    echo wp_get_attachment_image((int) $icon, 'thumbnail', false, ['class' => 'ct-join__filter-icon']);
                                }
                                ?>
                                <span><?php echo esc_html(get_sub_field('text')); ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (have_rows('join_advantage_stats', $page_id)) : ?>
                <div class="ct-join__advantage-stats" data-counter-group>
                    <?php while (have_rows('join_advantage_stats', $page_id)) : the_row(); ?>
                        <?php
                        $number   = (float) get_sub_field('number');
                        $prefix   = (string) get_sub_field('prefix');
                        $suffix   = (string) get_sub_field('suffix');
                        $duration = (int) get_sub_field('duration');
                        ?>
                        <article class="ct-join__advantage-stat">
                            <div
                                class="ct-join__counter"
                                data-counter
                                data-value="<?php echo esc_attr($number); ?>"
                                data-prefix="<?php echo esc_attr($prefix); ?>"
                                data-suffix="<?php echo esc_attr($suffix); ?>"
                                data-duration="<?php echo esc_attr($duration ?: 1600); ?>"
                            >
                                <?php echo esc_html($prefix . '0' . $suffix); ?>
                            </div>
                            <div class="ct-join__advantage-stat-content">
                                <p><?php echo esc_html(get_sub_field('label')); ?></p>
                                <span><?php echo esc_html(get_sub_field('text')); ?></span>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="ct-join__section ct-join__benefits">
        <div class="ct-join__container ct-join__center">
            <?php if ($eyebrow = get_field('join_benefits_eyebrow', $page_id)) : ?>
                <div class="ct-join__eyebrow"><?php echo esc_html($eyebrow); ?></div>
            <?php endif; ?>

            <?php if ($title = get_field('join_benefits_title', $page_id)) : ?>
                <h2><?php echo wp_kses_post($title); ?></h2>
            <?php endif; ?>

            <?php if (have_rows('join_benefits_cards', $page_id)) : ?>
                <div class="ct-join__benefit-grid">
                    <?php while (have_rows('join_benefits_cards', $page_id)) : the_row(); ?>
                        <article class="ct-join__benefit-card">
                            <?php
                            $card_image = get_sub_field('image');
                            if ($card_image) {
                                echo '<div class="ct-join__benefit-image-wrap">';
                                    echo wp_get_attachment_image((int) $card_image, 'large', false, ['class' => 'ct-join__benefit-image']);
                                echo '</div>';
                            }
                            ?>
                            <div class="ct-join__benefit-content">
                                <h3><?php echo esc_html(get_sub_field('title')); ?></h3>
                                <p><?php echo esc_html(get_sub_field('text')); ?></p>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="ct-join__section ct-join__testimonials">
        <div class="ct-join__container ct-join__center">
            <?php if ($eyebrow = get_field('join_testimonials_eyebrow', $page_id)) : ?>
                <div class="ct-join__eyebrow"><?php echo esc_html($eyebrow); ?></div>
            <?php endif; ?>
            <div class="title-arrows">
            <?php if ($title = get_field('join_testimonials_title', $page_id)) : ?>
                <h2><?php echo wp_kses_post($title); ?></h2>
            <?php endif; ?>
            <div class="ct-join__testimonial-arrows">
                <button
                    type="button"
                    class="ct-join__testimonial-arrow"
                    data-testimonial-prev
                    aria-label="הקודם"
                >
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 6l6 6-6 6"></path>
                    </svg>
                </button>

                <button
                    type="button"
                    class="ct-join__testimonial-arrow"
                    data-testimonial-next
                    aria-label="הבא"
                >
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 6l-6 6 6 6"></path>
                    </svg>
                </button>
            </div>
            </div>
            <?php if (have_rows('join_testimonials', $page_id)) : ?>
                <div class="ct-join__testimonial-slider">

                    <div
                        class="ct-join__testimonial-grid"
                        data-testimonial-track
                    >
                        <?php while (have_rows('join_testimonials', $page_id)) : the_row(); ?>
                            <blockquote
                                class="ct-join__testimonial"
                                data-testimonial-card
                            >

                                <div class="ct-join__testimonial-stars">
                                    <?php for ($i = 0; $i < 5; $i++) : ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24"
                                            fill="#F5A623" stroke="none">
                                            <path d="M12 2l2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.8 6.1 20.8l1.2-6.6L2.5 9l6.6-.9z"></path>
                                        </svg>
                                    <?php endfor; ?>
                                </div>

                                <div class="ct-join__quote">
                                    <div class="ct-join__quote-text">
                                        <?php echo wp_kses_post(get_sub_field('quote')); ?>
                                    </div>
                                </div>

                                <footer>
                                    <div class="ct-join__quote-text">
                                        <strong>
                                            <?php echo esc_html(get_sub_field('name')); ?>
                                        </strong>

                                        <span>
                                            <?php echo esc_html(get_sub_field('role')); ?>
                                        </span>
                                    </div>

                                    <div class="ct-join__quote-icon">
                                        <span>
                                            <?php
                                            echo esc_html(
                                                mb_substr(
                                                    get_sub_field('name'),
                                                    0,
                                                    1,
                                                    'UTF-8'
                                                )
                                            );
                                            ?>
                                        </span>
                                    </div>
                                </footer>
                            </blockquote>
                        <?php endwhile; ?>
                    </div>

                    <div
                        class="ct-join__testimonial-dots"
                        data-testimonial-dots
                    ></div>

                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="ct-join__section ct-join__pricing" id="pricing">
        <div class="ct-join__container ct-join__center">
            <?php if ($title = get_field('join_pricing_title', $page_id)) : ?>
                <h2><?php echo wp_kses_post($title); ?></h2>
            <?php endif; ?>

            <?php if ($text = get_field('join_pricing_text', $page_id)) : ?>
                <div class="ct-join__section-text"><?php echo wp_kses_post($text); ?></div>
            <?php endif; ?>

            <?php if (have_rows('join_pricing_plans', $page_id)) : ?>
                <div class="ct-join__plans">
                    <?php while (have_rows('join_pricing_plans', $page_id)) : the_row(); ?>
                        <article class="ct-join__plan<?php echo get_sub_field('highlight') ? ' is-highlighted' : ''; ?>">
                            <?php if ($badge = get_sub_field('badge')) : ?>
                                <div class="ct-join__plan-badge"><?php echo esc_html($badge); ?></div>
                            <?php endif; ?>

                            <h3><?php echo esc_html(get_sub_field('name')); ?></h3>
                            <div class="ct-join__price"><?php echo esc_html(get_sub_field('price')); ?></div>

                            <?php if ($description = get_sub_field('description')) : ?>
                                <p class="ct-join__plan-description"><?php echo esc_html($description); ?></p>
                            <?php endif; ?>

                            <?php if (have_rows('features', $page_id)) : ?>
                                <ul>
                                    <?php while (have_rows('features', $page_id)) : the_row(); ?>
                                        <li><?php echo esc_html(get_sub_field('text')); ?></li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

            <?php $button('join_pricing_button'); ?>

            <?php if ($note = get_field('join_pricing_note', $page_id)) : ?>
                <div class="ct-join__note"><?php echo esc_html($note); ?></div>
            <?php endif; ?>
        </div>
    </section>

    <section class="ct-join__final-cta">
        <div class="ct-join__container ct-join__center">
            <?php if ($title = get_field('join_cta_title', $page_id)) : ?>
                <h2><?php echo wp_kses_post($title); ?></h2>
            <?php endif; ?>

            <?php if ($text = get_field('join_cta_text', $page_id)) : ?>
                <div class="ct-join__section-text"><?php echo wp_kses_post($text); ?></div>
            <?php endif; ?>

            <?php $button('join_cta_button'); ?>

            <?php if ($note = get_field('join_cta_note', $page_id)) : ?>
                <div class="ct-join__note"><?php echo esc_html($note); ?></div>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php get_footer(); ?>
