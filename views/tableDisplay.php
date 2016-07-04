<?php

?>
<div class="col-lg-6 col-sm-12">
<table class='statisticstable table table-bordered'>
    <thead>
        <tr class='success'>
            <th colspan='4'>
                <strong>
                    <?php echo sprintf(gT("Field summary for %s"),$title); ?>
                </strong>
            </th>
        </tr>
        <tr>
            <th>
                <strong>
                    <?php eT("Answer");?>
                </strong>
            </th>
            <?php foreach($aSubQUestion as $sSubQuestion) {?>
            <th>
                <strong>
                    <?php echo $sSubQuestion;?>
                </strong>
            </th>
            <?php } ?>
        </tr>
        <tr>
            <th>
                <strong>
                    <?php eT("Answer");?>
                </strong>
            </th>
            <?php foreach($aSubQUestion as $sSubQuestion) {?>
            <th>
                <strong><?php eT("Count"); ?></strong>
            </th>
            <th>
                <strong><?php eT("Percentage");?></strong>
            </th>
            <?php } ?>
        </tr>
    </thead>
</table>
