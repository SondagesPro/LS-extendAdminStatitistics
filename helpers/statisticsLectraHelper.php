<?php
/**
 * Description
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2016 Lectra <http://www.sondages.pro>
 * @copyright 2016 Denis Chenu <http://www.sondages.pro>
 * @license GPL v3
 * @version 0.0.1
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

class statisticsLectraHelper
{

  /**
   * The survey id
   */
  public $iSurveyId;
  /**
   * The question id
   */
  public $iQid;
  /**
   * The type of export (only html is allowed actually)
   */
  public $sType='html';
  /**
   * The language for stats
   */
  public $sLanguage;
  /**
   * Extra conditions in sql string from LimesURVEY CORE
   */
  public $sql="";
  /**
   * boolena add graph nor not
   */
  public $addGraph=true;

  /**
   * Data for rendering
   */
  private $aRenderData=array();

    /**
     * Question type managed at lectra way
     */
  public $aSimpleArrayStat=array(
        "F",
        "A",
        "B",
        "C",
        "E",
        "H"
    );

  protected $pChartCache;
  /**
   * Some config for pChart
   * must be moved in specific class when rework for core
   */
  protected $apChartData=array();

  /**
   * Construct with params from global stat, always
   */
  function statisticsLectraHelper($iSurveyId,$sLanguage,$sql="",$sType='html',$addGraph=true,$aLectraQuestionType=array(),$objet=null) {
    $this->iSurveyId = $iSurveyId;
    $this->sLanguage = $sLanguage;
    $this->sql = $sql;
    $this->sType = $sType;
    $this->addGraph = $addGraph;
    $this->aLectraQuestionType = $aLectraQuestionType;

    $this->aRenderData['display']=$this->getGlobalDisplay();
    if($sType=="pdf" && $addGraph)
    {

      $this->apChartData['chartfontfile']=$this->getFontFile(Yii::app()->getConfig("chartfontfile"));
      if(!$this->apChartData['chartfontfile'])
      {
        $addGraph=false;
      }
      else
      {
        require_once(APPPATH.'/third_party/pchart/pchart/pChart.class');
        require_once(APPPATH.'/third_party/pchart/pchart/pData.class');
        require_once(APPPATH.'/third_party/pchart/pchart/pCache.class');
        $this->pChartCache=new pCache(App()->getConfig("tempdir").'/');// or runtime ?
        $this->apChartData['rootdir'] = Yii::app()->getConfig("rootdir");
        $this->apChartData['homedir'] = Yii::app()->getConfig("homedir");
        $this->apChartData['homeurl'] = Yii::app()->getConfig("homeurl");
        $this->apChartData['admintheme'] = Yii::app()->getConfig("admintheme");
        $this->apChartData['scriptname'] = Yii::app()->getConfig("scriptname");
        $this->apChartData['chartfontsize'] = Yii::app()->getConfig("chartfontsize");
      }

    }
    if($sType=="html")
    {
      $assetUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/../assets/');
      if(App()->getConfig('debug'))
      {
        App()->getClientScript()->registerScriptFile(App()->getConfig('adminscripts').'Chart.min.js');
      }
      else
      {
        App()->getClientScript()->registerScriptFile(Yii::app()->assetManager->publish(App()->getConfig('publicdir').DIRECTORY_SEPARATOR."scripts".DIRECTORY_SEPARATOR."admin") . '/Chart.min.js');
      }

      App()->getClientScript()->registerScriptFile(Yii::app()->assetManager->publish(dirname(__FILE__) . '/../assets/Chart.StackedBar.js'));
      App()->getClientScript()->registerScriptFile(Yii::app()->assetManager->publish(dirname(__FILE__) . '/../assets/jquery.lazyload-any.js'));

      App()->getClientScript()->registerScriptFile(Yii::app()->assetManager->publish(dirname(__FILE__) . '/../assets/extendStatitistics.js'));
      App()->getClientScript()->registerCssFile(Yii::app()->assetManager->publish(dirname(__FILE__) . '/../assets/extendStatitistics.css'));
      App()->getClientScript()->registerScript("chartjsExtendedData","var chartjsExtendedData = chartjsExtendedData || []",CClientScript::POS_BEGIN);
    }
  }
    /**
     * get HTML statitics
     * @param integer $iSurveyId : the survey id
     * @param integer $iQid : the question id
     * @param string $sLanguage : language code
     * @param array $aColumns : columns to get the statitistics
     * @param string $sql : the where part of select
     *
     * @return $string Html to produce
     */
  public function getHtmlStatistics($iQid,$aColumns=array(),$aCoreAnswers=array())
  {
    $oQuestion=Question::model()->find("qid=:qid AND language=:language",array(':qid'=>$iQid,":language"=>$this->sLanguage));
    $this->iQid=$iQid;
    $this->aRenderData['oQuestion']=$oQuestion;
    if(in_array($oQuestion->type,$this->aSimpleArrayStat))
    {
        $this->getSimpleArrayStat($iQid,$aColumns,$aCoreAnswers);
        return App()->controller->renderPartial("extendAdminStatitistics.views.arrayQuestionDisplay",$this->aRenderData,true,false);
    }
    else
    {
        throw new CException("Error in extendAdminStatitistics plugin : Not valid question type {$oQuestion->type}");
    }

  }
    /**
     * get Simpe HTML statitics (graph only)
     * @param integer $iSurveyId : the survey id
     * @param integer $iQid : the question id
     * @param string $sLanguage : language code
     * @param array $aColumns : columns to get the statitistics
     * @param string $sql : the where part of select
     *
     * @return $string Html to produce
     */
  public function getSimpleHtmlStatistics($iQid,$aColumns=array(),$aCoreAnswers=array())
  {
    $oQuestion=Question::model()->find("qid=:qid AND language=:language",array(':qid'=>$iQid,":language"=>$this->sLanguage));
    $this->iQid=$iQid;
    $this->aRenderData['oQuestion']=$oQuestion;
    if(in_array($oQuestion->type,$this->aSimpleArrayStat))
    {
        $this->getSimpleArrayStat($iQid,$aColumns,$aCoreAnswers);
        return App()->controller->renderPartial("extendAdminStatitistics.views.graph.arrayQuestionSimple",$this->aRenderData,true,false);
    }
    else
    {
        throw new CException("Error in extendAdminStatitistics plugin : Not valid question type {$oQuestion->type}");
    }

  }

  public function getPdfStatistics($iQid,$aColumns=array(),$aCoreAnswers=array())
  {
    $oQuestion=Question::model()->find("qid=:qid AND language=:language",array(':qid'=>$iQid,":language"=>$this->sLanguage));
    $this->iQid=$iQid;
    if(in_array($oQuestion->type,$this->aSimpleArrayStat))
    {
        //return $this->getSimpleArrayStat($iQid,$aColumns,$aCoreAnswers);
        /* From previous behaviour */
      $this->getSimpleArrayStat($iQid,$aColumns,$aCoreAnswers);
      return array(
        'title'=>sprintf(gT("Field summary for %s"),$oQuestion->title),
        'subtitle'=>viewHelper::flatEllipsizeText($oQuestion->question,true,false),
        'htmlTable'=>App()->controller->renderPartial("extendAdminStatitistics.views.data.arrayQuestionPdf",$this->aRenderData,true,false),
        'graphImageName'=>$this->getGraphImage($iQid)
      );
      //$this->pdf->writeHTML($htmlTable, true, false, true, false, '');
      //~ $headPDF = array();
      //~ $headPDF[] = array(gT("Answer"),gT("Count"),gT("Percentage"));
      //~ $tablePDF[] = array($label[$i],$grawdata[$i],sprintf("%01.2f", $gdata[$i])."%", "");
      //~ $this->pdf->headTable($headPDF, $tablePDF);
      //~ $this->pdf->tablehead($footPDF);
      //~ $this->pdf->AddPage('P','A4');
      //~ $this->pdf->titleintopdf($pdfTitle,$titleDesc);
      //~ $cachefilename = createChart($qqid, $qsid, $bShowPieChart, $lbl, $gdata, $grawdata, $MyCache, $sLanguage, $outputs['qtype']);
      //~ $this->pdf->Image($tempdir."/".$cachefilename, 0, 70, 180, 0, '', Yii::app()->getController()->createUrl("admin/survey/sa/view/surveyid/".$surveyid), 'B', true, 150,'C',false,false,0,true);

    }
    else
    {
        throw new CException("Error in extendAdminStatitistics plugin : Not valid question type {$oQuestion->type}");
    }

  }

  public function getSimpleArrayStat($iQid,$aColumns=array(),$aCoreAnswers=array())
  {
    $aoSubQuestions=Question::model()->findAll(array(
      "condition"=>"parent_qid=:parent_qid AND language=:language",
      "order"=>"question_order",
      "params"=>array(':parent_qid'=>$iQid,":language"=>$this->sLanguage)
    ));
    $aSubQuestions=array();
    $aStatData=array();
    $aAnswers=array();
    foreach($aCoreAnswers as $aCoreAnswer)
    {
      $aAnswers[$aCoreAnswer[0]]=array("value"=>$aCoreAnswer[0],"text"=>$aCoreAnswer[1]);
    }
    /* @todo : For array 5 and 10 : add medium */
    if(!App()->request->getPost('noncompleted'))
    {
      $aAnswers['null_value']=array("value"=>null,"text"=>gT("Not displayed"));
    }
    $this->aRenderData['aAnswers']=$aAnswers;
    foreach($aoSubQuestions as $oSubQuestion)
    {
      $sColumn="{$oSubQuestion->sid}X{$oSubQuestion->gid}X{$oSubQuestion->parent_qid}{$oSubQuestion->title}";

      if(empty($aColumns) || in_array($sColumn,$aColumns))
      {
        $aSubQuestions[$oSubQuestion->title]=$oSubQuestion->question;
        $aStatData[$oSubQuestion->title]=array();
        foreach($aAnswers as $kAnswer=>$aAnswer)
        {
          $aStatData[$oSubQuestion->title][$kAnswer]=$this->getCount($sColumn,$aAnswer["value"]);
        }
      }
    }

    $this->aRenderData['aSubQuestions']=$aSubQuestions;
    $this->aRenderData['iSubquestionsCount']=count($aSubQuestions);
    $this->aRenderData['aStatData']=$aStatData;
  }
  /**
   * Some global settings for displaying
   *
   * @return array
   */
  private function getGlobalDisplay()
  {

    $statsColumns=App()->request->getPost('stats_columns');
    switch($statsColumns)
    {
      case "1":
        $aDisplay=array(
          'nbcols'=> 12,
          'canvaWidth'=> 1150,
          'canvaHeight'=>800,
        );
      break;
      case "3":
        $aDisplay=array(
          'nbcols'=> 4,
          'canvaWidth'=> 333,
          'canvaHeight'=>500,
        );
      break;
      default:
        $aDisplay=array(
          'nbcols'=> 6,
          'canvaWidth'=> 500,
          'canvaHeight'=>500,
        );
      break;
    }
    return $aDisplay;
  }
  /**
   * function to generate the graph url using $this->renderData
   * @return name of the generated graph file
   */
  private function getGraphImage()
  {
    if(!$this->addGraph)
    {
      return;
    }
    if($moreDataThanAllowed=$this->getMoreDataThanAllowed())
    {
      return App()->getConfig("tempdir").'/'.$moreDataThanAllowed;
    }
    $aSubQuestions=$this->aRenderData['aSubQuestions'];
    $aStatData=$this->aRenderData['aStatData'];
    $aAnswers=$this->aRenderData['aAnswers'];
    unset($aAnswers['null_value']);

    $DataSet = new pData;
    foreach($aSubQuestions as $sTitle=>$sText)
    {
      $aData=array();
      foreach($aAnswers as $kAnswer=>$aAnswer)
      {

        $aData[]=$aStatData[$sTitle][$kAnswer];
      }
      $DataSet->AddPoint($aData,$sTitle);
    }
    $aData=array();
    foreach($aAnswers as $kAnswer=>$aAnswer)
    {

      $aData[]=html_entity_decode($aAnswer['text'],null,'UTF-8');
    }
    $DataSet->AddPoint($aData,"LabelSeries");
    //~ foreach($aSubQuestions as $sTitle=>$sText)
    //~ {
      //~ $DataSet->AddSerie($sTitle);
    //~ }
    $DataSet->AddAllSeries();
    $DataSet->SetAbsciseLabelSerie("LabelSeries");
    $DataSet->RemoveSerie("LabelSeries");
    foreach($aSubQuestions as $sTitle=>$sText)
    {
      $DataSet->SetSerieName(html_entity_decode($sText,null,'UTF-8'),$sTitle);
    }
    if ($this->pChartCache->IsInCache("graph".$this->iSurveyId.$this->sLanguage.$this->iQid,$DataSet->GetData()) && Yii::app()->getConfig('debug')<2)
    {
        //~ $cachefilename=basename($this->pChartCache->GetFileFromCache("graph".$this->iSurveyId.$this->sLanguage.$this->iQid,$DataSet->GetData()));
    }
    else
    {
      $admintheme = Yii::app()->getConfig("admintheme");
      $rootdir=$this->apChartData['rootdir'];
      $chartfontfile=$this->apChartData['chartfontfile'];
      $chartfontsize=$this->apChartData['chartfontsize'];
      $homedir=$this->apChartData['homedir'];


      $graph = new pChart(1,1);
      $graph->setFontProperties($this->apChartData['rootdir'].DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.$this->apChartData['chartfontfile'],$this->apChartData['chartfontsize']);
      $legendsize=$graph->getLegendBoxSize($DataSet->GetDataDescription());
      if ($legendsize[1]<320) $gheight=420; else $gheight=$legendsize[1]+100;
      $graph = new pChart(690+$legendsize[0],$gheight);
      $graph->drawFilledRectangle(0,0,690+$legendsize[0],$gheight,254,254,254,false);
      $graph->loadColorPalette($homedir.DIRECTORY_SEPARATOR.'styles'.DIRECTORY_SEPARATOR.$admintheme.DIRECTORY_SEPARATOR.'images/limesurvey.pal');
      $graph->setFontProperties($rootdir.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.$chartfontfile,$chartfontsize);
      $graph->setGraphArea(50,30,500,$gheight-60);
      $graph->drawFilledRoundedRectangle(7,7,523+$legendsize[0],$gheight-7,5,254,255,254);
      $graph->drawRoundedRectangle(5,5,525+$legendsize[0],$gheight-5,5,230,230,230);
      $graph->drawGraphArea(254,254,254,TRUE);
      $graph->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_ADDALLSTART0,150,150,150,TRUE,30,0,TRUE,1,false);
      $graph->drawGrid(4,TRUE,230,230,230,50);
      // Draw the 0 line
      $graph->setFontProperties($rootdir.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.$chartfontfile,$chartfontsize);
      $graph->drawTreshold(0,143,55,72,TRUE,TRUE);

      $graph->drawStackedBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(),TRUE);
      $graph->setFontProperties($rootdir.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.$chartfontfile, $chartfontsize);
      $graph->drawLegend(510,30,$DataSet->GetDataDescription(),250,250,250);

      $this->pChartCache->WriteToCache("graph".$this->iSurveyId.$this->sLanguage.$this->iQid,$DataSet->GetData(),$graph);
      //~ $cachefilename=basename($cache->GetFileFromCache("graph".$this->iSurveyId.$this->sLanguage.$this->iQid,$DataSet->GetData()));
      unset($graph);
    }

    return App()->getConfig("tempdir").'/'.basename($this->pChartCache->GetFileFromCache("graph".$this->iSurveyId.$this->sLanguage.$this->iQid,$DataSet->GetData()));
  }

  /**
   * test if we can do the graph, return a image with text if not
   */
  private function getMoreDataThanAllowed()
  {

    if (count($this->aRenderData['aSubQuestions'])>20)
    {
        $DataSet = array(1=>array(1=>1));
        if ($this->pChartCache->IsInCache("graph".$this->iSurveyId.$this->sLanguage.$this->iQid,$DataSet))
        {
            $GraphFileName=basename($this->pChartCache->GetFileFromCache("graph".$this->iSurveyId.$this->sLanguage.$this->iQid,$DataSet));
        }
        else
        {
            $graph = new pChart(690,200);
            $graph->loadColorPalette($this->apChartData['homedir'].DIRECTORY_SEPARATOR.'styles'.DIRECTORY_SEPARATOR.$this->apChartData['admintheme'].DIRECTORY_SEPARATOR.'images/limesurvey.pal');
            $graph->setFontProperties($this->apChartData['rootdir'].DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.$this->apChartData['chartfontfile'],$this->apChartData['chartfontsize']);
            $graph->setFontProperties($this->apChartData['rootdir'].DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.$this->apChartData['chartfontfile'],$this->apChartData['chartfontsize']);
            $graph->drawTitle(0,0,gT('Sorry, but this question has too many answer options to be shown properly in a graph.','unescaped'),30,30,30,690,200);
            $this->pChartCache->WriteToCache("graph".$this->iSurveyId.$this->sLanguage.$this->iQid,$DataSet,$graph);
            $GraphFileName=basename($this->pChartCache->GetFileFromCache("graph".$this->iSurveyId.$this->sLanguage.$this->iQid,$DataSet));
            unset($graph);
        }
        return $GraphFileName;
    }
  }
  /**
   * Return the font files to be used, null if an error happen
   */
  private function getFontFile($sFontFile)
  {
    /* Don't test again and again font file */
    static $bErrorGenerate=false;
    if($bErrorGenerate)
      return;

    /* If set to auto, or invalid : try alternate */
    $alternatechartfontfile = Yii::app()->getConfig("alternatechartfontfile");
    if ($sFontFile=='auto' || !is_file($rootdir."/fonts/".$sFontFile))
    {
        // Tested with ar,be,el,fa,hu,he,is,lt,mt,sr, and en (english)
        // Not working for hi, si, zh, th, ko, ja : see $config['alternatechartfontfile'] to add some specific language font
        $sFontFile='DejaVuSans.ttf';
        if(array_key_exists($this->sLanguage,$alternatechartfontfile))
        {
            $neededfontfile = $alternatechartfontfile[$this->sLanguage];
            if(is_file($rootdir."/fonts/".$neededfontfile))
            {
                $sFontFile=$neededfontfile;
            }
            else
            {
                Yii::app()->setFlashMessage(sprintf(gT('The fonts file %s was not found in <limesurvey root folder>/fonts directory. Please, see the txt file for your language in fonts directory to generate the charts.'),$neededfontfile),'error');
                $bErrorGenerate=true;// Don't do a graph again.
                return;// break
            }
        }
    }
    return $sFontFile;
  }
    /**
     * Get the count for a any question type
     * @param : $sColumn : question title
     * @return integer
     */
    private function getCount($sColumn,$sValue=null)
    {
        $aCount=array(); // Go to cache ?
        if(isset($aCount[$sColumn][$sValue]))
            return $aCount[$sColumn][$sValue];
        $sQuotedColumn=Yii::app()->db->quoteColumnName($sColumn);
        $oCriteria = new CDbCriteria;
        $completeFilter=incompleteAnsFilterState();
        switch ($completeFilter)
        {
          case "incomplete":
            $oCriteria->condition="submitdate IS NULL";
            break;
          case "complete":
            $oCriteria->condition="submitdate IS NOT NULL";
            break;
          default:
            $oCriteria->condition="1=1";
            break;
        }
        if (!empty($this->sql))
        {
          $oCriteria->AddCondition($this->sql);
        }

        if($sValue==="")
        {
          $oCriteria->addCondition("{$sQuotedColumn} ='' ");
        }
        elseif(!is_null($sValue))
        {
          $oCriteria->compare($sQuotedColumn,$sValue);
        }
        else
        {
          $oCriteria->addCondition("{$sQuotedColumn} IS NULL");
        }
        $aCount[$sColumn][$sValue]=intval(SurveyDynamic::model($this->iSurveyId)->count($oCriteria));
        return $aCount[$sColumn][$sValue];
    }
}
