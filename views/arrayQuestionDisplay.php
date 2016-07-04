<div class="col-lg-<?php echo $display['nbcols']; ?> sol-sm-12">
  <table class='statisticstable table table-bordered'>
    <thead>
      <tr class='success'>
        <th colspan='<?php echo $iSubquestionsCount*3+1; ?>'>
          <strong>
            <?php echo sprintf(gT("Field summary for %s"),$questionCode); ?>
          </strong>
        </th>
      </tr>
      <tr>
        <th colspan='<?php echo $iSubquestionsCount*3+1; ?>'>
          <?php echo CHtml::tag("strong",array('title'=>$questionText),viewHelper::flatEllipsizeText($questionText,true,100)); ?>
          </strong>
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
<div class="row">
<?php
/**
 * Do the array for the graph
 * @see https://github.com/chartjs/Chart.js/blob/v1.1.1/docs/02-Bar-Chart.md
 **/
unset($aAnswers['null_value']);
$aDataSets=array();
$ChartsColor = Array('20,130,200','232,95,51','34,205,33','210,211,28','134,179,129','201,171,131','251,231,221','23,169,161','167,187,213','211,151,213','147,145,246','147,39,90','250,250,201','201,250,250','94,0,94','250,125,127','0,96,201','201,202,250','0,0,127','250,0,250','250,250,0','0,250,250','127,0,127','127,0,0','0,125,127','0,0,250','0,202,250','201,250,250','201,250,201','250,250,151','151,202,250','251,149,201','201,149,250','250,202,151','45,96,250','45,202,201','151,202,0','250,202,0','250,149,0','250,96,0','184,230,115','102,128,64','220,230,207','134,191,48','184,92,161','128,64,112','230,207,224','191,48,155','230,138,115','128,77,64','230,211,207','191,77,48','80,161,126','64,128,100','207,230,220','48,191,130','25,25,179','18,18,125','200,200,255','145,145,255','255,178,0','179,125,0','255,236,191','255,217,128','255,255,0','179,179,0','255,255,191','255,255,128','102,0,153','71,0,107','234,191,255','213,128,255');

$count=0;
foreach($aSubQuestions as $sTitle=>$sText)
{
  $aData=array();
  foreach($aAnswers as $kAnswer=>$aAnswer)
  {

    $aData[]=$aStatData[$sTitle][$kAnswer];
  }
  $aDataSets[]=array(
    'label'=>viewHelper::flatEllipsizeText($sText,true,100),
    'fillColor'=>"rgba({$ChartsColor[$count]},0.2)",
    'strokeColor'=> "rgba({$ChartsColor[$count]},0.4)",
    'highlightFill'=>"rgba({$ChartsColor[$count]},0.75)",
    'highlightStroke'=>"rgba({$ChartsColor[$count]},1)",
    'data'=>$aData,
  );
  $count++;
}
$graphLabels=App()->getRequest()->getParam('graph_labels');
$aLabels=array();
foreach($aAnswers as $kAnswer=>$aAnswer)
{
    switch ($graphLabels)
    {
      case 'qtext':
        $aLabels[]=viewHelper::flatEllipsizeText($aAnswer['text'],true,40);
        break;
      case 'both':
        $aLabels[]=$aAnswer['value'].": ".viewHelper::flatEllipsizeText($aAnswer['text'],true,40);
        break;
      default:
        $aLabels[]=$aAnswer['value'];
    }
}
?>
<div class="col-lg-8 col-md-12 chartjs-wrapper">
<?php
  echo CHtml::tag("canvas",array(
    'class'=>"canvas-chart ",
    'id'=>"chartjs-q{$questionQid}",
    'width'=>$display['canvaWidth'],
    'height'=>$display['canvaHeight'],
    'color'=>'10', // ?
    'data-chartid'=>"q{$questionQid}",
    'data-charttype'=>'Bar',
  ),"",true);
  $sJsonData="chartjsExtendedData['q{$questionQid}']=".json_encode(array(
  "labels"=>$aLabels,
  "datasets"=>$aDataSets
  ));
  App()->getClientScript()->registerScript("chartjsExtendedData{$questionQid}",$sJsonData,CClientScript::POS_BEGIN);

?>
</div>
<?php
  echo CHtml::tag("div",array(
    'class'=>'legend col-lg-4 col-md-12',
    'id'=>"chartjs-legend-q{$questionQid}",
  ),"",true);
?>
</div>

</div>

