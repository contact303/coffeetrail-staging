<?php
/**
 * `days-of-week` filter: single-select dropdown (basic), multiselect checkboxes (advanced).
 * Order: Sunday first. AND across selected days in search (see filter class). Hebrew: יום שבת.
 *
 * Basic mirrors parent `dropdown.php` single mode (`required` + empty first option for md-group).
 *
 * @var \MyListing\Src\Listing_Types\Filters\Days_Of_Week $filter
 * @var string $location
 * @var string $onchange
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$choices = wp_json_encode( $filter->get_choices() );
$lt      = $filter->listing_type->get_slug();
$key     = $filter->get_form_key();
$label   = $filter->get_label();

$is_multiple = ( 'advanced-form' === $location ) && $filter->get_prop( 'multiselect' );

if ( 'basic-form' === $location ) : ?>
<dropdown-filter
	listing-type="<?php echo esc_attr( $lt ); ?>"
	filter-key="<?php echo esc_attr( $key ); ?>"
	location="<?php echo esc_attr( $location ); ?>"
	label="<?php echo esc_attr( $label ); ?>"
	:multiple="false"
	:choices="<?php echo esc_attr( $choices ); ?>"
	@input="<?php echo esc_attr( $onchange ); ?>"
	inline-template
>
	<div
		class="form-group explore-filter dropdown-filter md-group days-of-week-filter"
		:class="multiple ? 'dropdown-filter-multiselect' : ''"
	>
		<select
			ref="select"
			required
			placeholder=" "
			data-placeholder=" "
			:multiple="multiple"
			@select:change="handleChange"
		>
			<option v-if="!multiple"></option>
			<option v-for="choice in choices" :value="choice.value" :selected="isSelected(choice.value)">
				{{choice.label}}
			</option>
		</select>
		<label>{{label}}</label>
	</div>
</dropdown-filter>
<?php else : ?>
<checkboxes-filter
	listing-type="<?php echo esc_attr( $lt ); ?>"
	filter-key="<?php echo esc_attr( $key ); ?>"
	location="<?php echo esc_attr( $location ); ?>"
	label="<?php echo esc_attr( $label ); ?>"
	:choices="<?php echo esc_attr( $choices ); ?>"
	:multiple="<?php echo $is_multiple ? 'true' : 'false'; ?>"
	@input="<?php echo esc_attr( $onchange ); ?>"
	inline-template
>
	<div class="form-group form-group-tags explore-filter checkboxes-filter days-of-week-filter">
		<label>{{label}}</label>
		<ul class="tags-nav">
			<li v-for="choice, key in choices">
				<div class="md-checkbox">
					<input :id="filterId+key" :type="multiple ? 'checkbox' : 'radio'"
						:value="choice.value" v-model="selected" @change="updateInput">
					<label :for="filterId+key">{{choice.label}}</label>
				</div>
			</li>
		</ul>
	</div>
</checkboxes-filter>
<?php endif; ?>
