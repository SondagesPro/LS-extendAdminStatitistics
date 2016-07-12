<div class="col-lg-<?php echo $display['nbcols']; ?> sol-sm-12">
  <?php
    /* The table */
    Yii::app()->getController()->renderPartial("extendAdminStatitistics.views.data.arrayQuestion.",array(
      "iSubquestionsCount"=>$iSubquestionsCount,
      "oQuestion"=>$oQuestion,
      "display"=>$display,
      "aStatData"=>$aStatData,
      "aAnswers"=>$aAnswers,
      "aSubQuestions"=>$aSubQuestions,
    ));// Can use $_data_ for quickest way
    /* The graph */
    Yii::app()->getController()->renderPartial("extendAdminStatitistics.views.graph.arrayQuestion.",array(
      "oQuestion"=>$oQuestion,
      "display"=>$display,
      "aStatData"=>$aStatData,
      "aAnswers"=>$aAnswers,
      "aSubQuestions"=>$aSubQuestions,
    ));// Can use $_data_ for quickest way
  ?>
</div>

