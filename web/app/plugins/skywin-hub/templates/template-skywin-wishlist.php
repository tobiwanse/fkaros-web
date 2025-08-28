<?php

?>
<div class="skywin-hub-wishlist">

    <?php
    foreach( $args['items'] as $item ) {
            echo $item['FirstName'] . ' ' . $item['LastName'];
    }
    ?>
</div>
