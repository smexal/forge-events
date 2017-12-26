<?php if(!class_exists('raintpl')){exit;}?><div class="subnavigation">
    <div class="wrapper">
        <nav>
            <ul>
                <?php $counter1=-1; if( isset($items) && is_array($items) && sizeof($items) ) foreach( $items as $key1 => $value1 ){ $counter1++; ?>

                <li class="<?php echo $value1["active"];?>"><a href="<?php echo $value1["url"];?>"><?php echo $value1["title"];?></a></li>
                <?php } ?>

            </ul>
        </nav>
    </div>
</div>