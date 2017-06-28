<?php

$paginate_links = str_replace(
    '<ul class=\'page-numbers\'>',
    '<ul class=\'pagination\'>',
    $paginate_links
);
$paginate_links = str_replace(
    '<li><span class=\'page-numbers dots\'>',
    '<li><a href=\'javascript:;\'>',
    $paginate_links
);
$paginate_links = str_replace(
    '<li><span class=\'page-numbers current\'>',
    '<li class=\'current\'><a href=\'javascript:;\'>',
    $paginate_links
);
$paginate_links = str_replace( '</span>', '</a>', $paginate_links );
$paginate_links = str_replace(
    '<li><a href=\'#\'>&hellip;</a></li>',
    '<li><span class=\'dots\'>&hellip;</span></li>',
    $paginate_links
);
$paginate_links = preg_replace( '/\s*page-numbers/', '', $paginate_links );

// Display the pagination if more than one page is found
if ( $paginate_links ) {
    echo '<div class="pagination-centered">' . $paginate_links . '</div>';
}
