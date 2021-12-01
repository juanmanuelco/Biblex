<?php
global $tooltip_ref;
$ref_str = $tooltip_ref->get_string();

$query = bfox_blog_query_for_ref($tooltip_ref);
$count = 0;

$blogs_ref_title = __(' blog posts', 'bfox');
$blogs_links = __(' links', 'bfox');
$bible_reader = __('Bible Reader', 'bfox');
$bible_reader = __('Post Archive', 'bfox');
$write_post = __('Write a post', 'bfox');

if($lang == 'es'){
	$blogs_ref_title = ' publicaciones';
	$blogs_links = ' enlaces';
	$bible_reader = 'Leer la biblia';
	$post_archive = 'Publicaciones con similar referencia';
	$write_post = 'Escribir publicación';
}

?>

<div class="bfox-tooltip-posts">
	<div><?php echo $ref_str . $blogs_ref_title ?></div>
	<ul>
		<?php while($count < 10 && $query->have_posts()): $count++; $query->the_post() ?>
		<li><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></li>
		<?php endwhile ?>
		<?php if (0 == $count): ?>
		<li><?php _e('No blog posts', 'bfox') ?></li>
		<?php endif ?>
	</ul>
	<div><?php echo $ref_str . $blogs_links ?></div>
	<ul>
		<li><?php echo bfox_ref_bible_link(array('ref_str' => $ref_str, 'text' => $bible_reader, 'disable_tooltip' => true)) ?></li>
		<li><?php
            echo bfox_ref_blog_link(array('ref_str' => $ref_str, 'text' => $post_archive, 'disable_tooltip' => true))
            ?></li>

		<?php if (current_user_can('edit_posts')): ?>
		<li><?php echo bfox_blog_ref_write_link($ref_str, $write_post) ?></li>
		<?php endif ?>
	</ul>
</div>

<div class="bfox-tooltip-bible">
	<?php $iframe = new BfoxIframe($tooltip_ref, $lang) ?>
	<select class="bfox-iframe-select langer">
		<?php echo $iframe->select_options() ?>
	</select>
	<iframe sandbox="allow-same-origin allow-scripts allow-popups allow-forms" class="bfox-iframe bfox-tooltip-iframe" src="<?php echo $iframe->url() ?>"></iframe>
</div>
