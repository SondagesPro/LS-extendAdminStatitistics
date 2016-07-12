<table>
  <thead>
    <tr style="background-color:#ccc;color:#000;">
      <td>&nbsp;</td>
      <?php foreach($aSubQuestions as $sTitle=>$sText){
        echo CHtml::tag("th",array("colspan"=>2,"align"=>'center','style'=>'border-left-width:1px'),"<strong>".viewHelper::flatEllipsizeText($sText,true,100)." [".$sTitle."]</strong>");
      } ?>
    </tr>
  <tr  style="background-color:#ccc;color:#000;">
    <th 'align'='center'>
      <?php echo CHtml::tag("strong",array(),gT("Answer")); ?>
    </th>
      <?php foreach($aSubQuestions as $sTitle=>$sText){
          echo CHtml::tag("th",array('align'=>'center','style'=>'border-left-width:1px'),gT("Count"));
          echo CHtml::tag("th",array("align"=>'center'),gT("Percentage"));
          $maxForPc=array_sum($aStatData[$sTitle]);
      } ?>
  </tr>
  </thead>
  <?php
  $even=false;
  foreach($aAnswers as $kAnswer=>$aAnswer)
  {
    if($even){
      $bgcolor='#eee';
    }else{
      $bgcolor='#fff';
    }
    $even=!$even;
    echo CHtml::tag("tr",array('style'=>"background-color:{$bgcolor};"),false,false);
    echo CHtml::tag("th",array('class'=>'answer'),$aAnswer['text'].($aAnswer['value'] ? " (".$aAnswer['value'].")":""));

    foreach($aSubQuestions as $sTitle=>$sText)
    {
      echo CHtml::tag("td",array('class'=>'value','align'=>'center','style'=>'border-left-width:1px'),$aStatData[$sTitle][$kAnswer]);
      echo CHtml::tag("td",array('class'=>'value percentage','align'=>'center'),($maxForPc>0 ? round(($aStatData[$sTitle][$kAnswer]/$maxForPc)*100,2)."%" : "/"));
    }
    echo CHtml::closeTag("tr");
  }?>
</table>
