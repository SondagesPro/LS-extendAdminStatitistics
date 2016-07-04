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
  private $iSurveyId;
  /**
   * The type of export (only html is allowed actually)
   */
  private $sType='html';
  /**
   * The language for stats
   */
  private $sLanguage;
  /**
   * Extra conditions in sql string from LimesURVEY CORE
   */
  private $sql="";
  /**
   * boolena add graph nor not
   */
  private $addGraph=true;

  /**
   * Data for rendering
   */
  private $aRenderData=array();
  /**
   * Construct with params from global stat, always
   */
  function statisticsLectraHelper($iSurveyId,$sLanguage,$sql="",$sType='html',$addGraph=true) {
    $this->iSurveyId = $iSurveyId;
    $this->sLanguage = $sLanguage;
    $this->sql = $sql;
    $this->sType = $sType;
    $this->addGraph = $addGraph;
    $this->aRenderData['display']=$this->getGlobalDisplay();
    $jsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/../assets/extendStatitistics.js');
    App()->getClientScript()->registerScriptFile($jsUrl);
    App()->getClientScript()->registerScript("chartjsExtended","var chartjsExtended = chartjsExtended || []",CClientScript::POS_BEGIN);

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
    $this->aRenderData['questionText']=viewHelper::flatEllipsizeText($oQuestion->question,true,false);
    $this->aRenderData['questionCode']=$oQuestion->title;
    $this->aRenderData['questionQid']=$oQuestion->qid;
    switch($oQuestion->type)
    {
      case 'F':
        return $this->getSimpleArrayStat($iQid,$aColumns,$aCoreAnswers);
        break;
      default:
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
    if(App()->request->getPost('noncompleted')==="0")
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
    return App()->controller->renderPartial("extendAdminStatitistics.views.arrayQuestionDisplay",$this->aRenderData,true,false);
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
