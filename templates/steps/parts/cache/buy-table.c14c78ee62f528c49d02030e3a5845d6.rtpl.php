<?php if(!class_exists('raintpl')){exit;}?><table id="ticket-buy-table" ajax-url="<?php echo $buytableurl;?>" class="content-table">
    <tr>
    <?php $counter1=-1; if( isset($tr) && is_array($tr) && sizeof($tr) ) foreach( $tr as $key1 => $value1 ){ $counter1++; ?>

        <th>
        <?php echo $value1["name"];?>

        </th>
    <?php } ?>

    </tr>
    <?php $counter1=-1; if( isset($td) && is_array($td) && sizeof($td) ) foreach( $td as $key1 => $value1 ){ $counter1++; ?>

    <tr>
        <?php $counter2=-1; if( isset($value1) && is_array($value1) && sizeof($value1) ) foreach( $value1 as $key2 => $value2 ){ $counter2++; ?>

            <td><?php echo $value2;?></td>
        <?php } ?>

    </tr>
    <?php } ?>

    <?php if( $tb ){ ?>

        <tfoot>
        <tr>
            <th class="sum" colspan="<?php echo $tb["colspan"];?>"><?php echo $tb["label"];?></th>
            <th class="total"><?php echo $tb["total"];?></th>
        </tr>
        <tr>
            <td colspan="<?php echo $tb["colspan"];?>"></td>
            <td><?php echo $tb["bbuyall"];?></td>
        </tr>
        </tfoot>
    <?php } ?>

</table>