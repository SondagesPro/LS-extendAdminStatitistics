<div class="row table-responsive">
  <table class='statisticstable table table-bordered'>
    <thead>
      <tr class='success'>
        <th colspan='<?php echo $iSubquestionsCount*3+1; ?>'>
          <strong>
            <?php echo sprintf(gT("Field summary for %s"),$oQuestion->title); ?>
          </strong>
        </th>
      </tr>
      <tr>
        <th colspan='<?php echo $iSubquestionsCount*3+1; ?>'>
          <?php echo CHtml::tag("strong",array('title'=>viewHelper::flatEllipsizeText($oQuestion->question,true,false)),viewHelper::flatEllipsizeText($oQuestion->question,true,100)); ?>
        </th>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <?php foreach($aSubQuestions as $sTitle=>$sText){
          echo CHtml::tag("th",array("colspan"=>2,'title'=>$sText),viewHelper::flatEllipsizeText($sText,true,100)." [".$sTitle."]");
        } ?>
      </tr>
      <tr>
        <th>
          <strong>
              <?php eT("Answer");?>
          </strong>
        </th>
        <?php foreach($aSubQuestions as $sTitle=>$sText){
          echo CHtml::tag("th",array(),gT("Count"));
          echo CHtml::tag("th",array(),gT("Percentage"));
          $maxForPc=array_sum($aStatData[$sTitle]);
        } ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach($aAnswers as $kAnswer=>$aAnswer)
      {
        echo CHtml::tag("tr",array(),false,false);
        echo CHtml::tag("th",array('class'=>'answer'),$aAnswer['text'].($aAnswer['value'] ? " (".$aAnswer['value'].")":""));

        foreach($aSubQuestions as $sTitle=>$sText)
        {
          echo CHtml::tag("td",array('class'=>'value'),$aStatData[$sTitle][$kAnswer]);
          echo CHtml::tag("td",array('class'=>'value percentage'),($maxForPc>0 ? round(($aStatData[$sTitle][$kAnswer]/$maxForPc)*100,2)."%" : "/"));
        }
        echo CHtml::closeTag("tr");
      }?>
    </tbody>
  </table>
</div>
