<?php
/**
 * Description
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2015 Denis Chenu <http://www.sondages.pro>
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
class adminStatisticsController extends statistics {

    function __construct($controller, $id)
    {
        parent::__construct($controller, $id);
    }
    public function index($surveyid = 0, $subaction = null)
    {
        $params=$this->_addPseudoParams($this->getController()->getActionParams());
        switch($params['sa'])
        {
            case 'simpleStatistics':
                $this->simpleStatistics($surveyid);
                Yii::app()->end();
                break;
            case 'listcolumn':
                $this->listcolumn(
                    $surveyid,
                    isset($params['column']) ? $params['column'] : "",
                    isset($params['sortby']) ? $params['sortby'] : "",
                    isset($params['sortmethod']) ? $params['sortmethod'] : "",
                    isset($params['sorttype']) ? $params['sorttype'] : ""
                );
                Yii::app()->end();
                break;
            case 'graph':
                $this->graph($surveyid);
                Yii::app()->end();
                break;
            default:
                $this->run($surveyid, $params['sa']);
                Yii::app()->end();
        }


    }
    /**
     * Original system by limesurvey
     * Updated call of helper to use own helper
     */
    public function run($surveyid = 0, $subaction = null)
    {
        $surveyid = sanitize_int($surveyid);
        $imageurl = Yii::app()->getConfig("imageurl");
        $aData = array('imageurl' => $imageurl);

        /*
         * We need this later:
         *  1 - Array Dual Scale
         *  5 - 5 Point Choice
         *  A - Array (5 Point Choice)
         *  B - Array (10 Point Choice)
         *  C - Array (Yes/No/Uncertain)
         *  D - Date
         *  E - Array (Increase, Same, Decrease)
         *  F - Array (Flexible Labels)
         *  G - Gender
         *  H - Array (Flexible Labels) by Column
         *  I - Language Switch
         *  K - Multiple Numerical Input
         *  L - List (Radio)
         *  M - Multiple choice
         *  N - Numerical Input
         *  O - List With Comment
         *  P - Multiple choice with comments
         *  Q - Multiple Short Text
         *  R - Ranking
         *  S - Short Free Text
         *  T - Long Free Text
         *  U - Huge Free Text
         *  X - Boilerplate Question
         *  Y - Yes/No
         *  ! - List (Dropdown)
         *  : - Array (Flexible Labels) multiple drop down
         *  ; - Array (Flexible Labels) multiple texts
         *  | - File Upload


         Debugging help:
         echo '<script language="javascript" type="text/javascript">alert("HI");</script>';
         */

        //split up results to extend statistics -> NOT WORKING YET! DO NOT ENABLE THIS!
        $showcombinedresults = 0;

        /*
         * this variable is used in the function shortencode() which cuts off a question/answer title
         * after $maxchars and shows the rest as tooltip
         */
        $maxchars = 50;

        //we collect all the output within this variable
        $statisticsoutput ='';

        //output for chosing questions to cross query
        $cr_statisticsoutput = '';

        // This gets all the 'to be shown questions' from the POST and puts these into an array
        $summary=returnGlobal('summary');
        $statlang=returnGlobal('statlang');

        //if $summary isn't an array we create one
        if (isset($summary) && !is_array($summary)) {
            $summary = explode("+", $summary);
        }

        //no survey ID? -> come and get one
        if (!isset($surveyid)) {$surveyid=returnGlobal('sid');}

        //still no survey ID -> error
        $aData['surveyid'] = $surveyid;


        // Set language for questions and answers to base language of this survey
        $language = Survey::model()->findByPk($surveyid)->language;
        $aData['language'] = $language;


        //Call the javascript file
        $this->registerScriptFile( 'ADMIN_SCRIPT_PATH', 'statistics.js');
        $this->registerScriptFile( 'ADMIN_SCRIPT_PATH', 'json-js/json2.min.js');

        $aData['display']['menu_bars']['browse'] = gT("Quick statistics");

        //Select public language file
        $row  = Survey::model()->find('sid = :sid', array(':sid' => $surveyid));

        /*
         * check if there is a datestamp available for this survey
         * yes -> $datestamp="Y"
         * no -> $datestamp="N"
         */
        $datestamp = $row->datestamp;

        // 1: Get list of questions from survey

        /*
         * We want to have the following data
         * a) "questions" -> all table namens, e.g.
         * qid
         * sid
         * gid
         * type
         * title
         * question
         * preg
         * help
         * other
         * mandatory
         * lid
         * lid1
         * question_order
         * language
         *
         * b) "groups" -> group_name + group_order *
         */

        //store all the data in $rows
        $rows = Question::model()->getQuestionList($surveyid, $language);

        //SORT IN NATURAL ORDER!
        usort($rows, 'groupOrderThenQuestionOrder');

        //put the question information into the filter array
        $filters = array();
        $aGroups = array();
        $keyone = 0;
        foreach ($rows as $row)
        {
            //store some column names in $filters array

            $filters[]=array($row['qid'],
            $row['gid'],
            $row['type'],
            $row['title'],
            $row['group_name'],
            flattenText($row['question']));

            if (!in_array($row['group_name'], $aGroups))
            {
                //$aGroups[] = $row['group_name'];
                $aGroups[$row['group_name']]['gid'] = $row['gid'];
                $aGroups[$row['group_name']]['name'] = $row['group_name'];
            }
            $aGroups[$row['group_name']]['questions'][$keyone] = array($row['qid'],
            $row['gid'],
            $row['type'],
            $row['title'],
            $row['group_name'],
            flattenText($row['question'])); ;
            $keyone = $keyone+1;
        }
        $aData['filters'] = $filters;
        $aData['aGroups'] = $aGroups;

        //var_dump($filters);
        // SHOW ID FIELD

        $grapherror = false;
        $error = '';
        if (!function_exists("gd_info")) {
            $grapherror = true;
            $error.='<br />'.gT('You do not have the GD Library installed. Showing charts requires the GD library to function properly.');
            $error.='<br />'.gT('visit http://us2.php.net/manual/en/ref.image.php for more information').'<br />';
        }
        elseif (!function_exists("imageftbbox")) {
            $grapherror = true;
            $error.='<br />'.gT('You do not have the Freetype Library installed. Showing charts requires the Freetype library to function properly.');
            $error.='<br />'.gT('visit http://us2.php.net/manual/en/ref.image.php for more information').'<br />';
        }

        if ($grapherror)
        {
            unset($_POST['usegraph']);
        }


        //pre-selection of filter forms
        if (incompleteAnsFilterState() == "complete")
        {
            $selecthide="selected='selected'";
            $selectshow="";
            $selectinc="";
        }
        elseif (incompleteAnsFilterState() == "incomplete")
        {
            $selecthide="";
            $selectshow="";
            $selectinc="selected='selected'";
        }
        else
        {
            $selecthide="";
            $selectshow="selected='selected'";
            $selectinc="";
        }
        $aData['selecthide'] = $selecthide;
        $aData['selectshow'] = $selectshow;
        $aData['selectinc'] = $selectinc;
        $aData['error'] = $error;

        $survlangs = Survey::model()->findByPk($surveyid)->additionalLanguages;
        $survlangs[] = Survey::model()->findByPk($surveyid)->language;
        $aData['survlangs'] = $survlangs;
        $aData['datestamp'] = $datestamp;

        //if the survey contains timestamps you can filter by timestamp, too

        //Output selector

        //second row below options -> filter settings headline

        $filterchoice_state=returnGlobal('filterchoice_state');
        $aData['filterchoice_state'] = $filterchoice_state;


        /*
         * let's go through the filter array which contains
         *     ['qid'],
         ['gid'],
         ['type'],
         ['title'],
         ['group_name'],
         ['question'],
         ['lid'],
         ['lid1']);
         */

        $currentgroup='';
        $counter = 0;
        foreach ($filters as $key1 => $flt)
        {
            //is there a previous question type set?


            /*
             * remember: $flt is structured like this
             *  ['qid'],
             ['gid'],
             ['type'],
             ['title'],
             ['group_name'],
             ['question'],
             ['lid'],
             ['lid1']);
             */

            //SGQ identifier

            //full question title

            /*
             * Check question type: This question types will be used (all others are separated in the if clause)
             *  5 - 5 Point Choice
             G - Gender
             I - Language Switch
             L - List (Radio)
             M - Multiple choice
             N - Numerical Input
             | - File Upload
             O - List With Comment
             P - Multiple choice with comments
             Y - Yes/No
             ! - List (Dropdown) )
             */


            /////////////////////////////////////////////////////////////////////////////////////////////////
            //This section presents the filter list, in various different ways depending on the question type
            /////////////////////////////////////////////////////////////////////////////////////////////////

            //let's switch through the question type for each question
            switch ($flt[2])
            {
                case "K": // Multiple Numerical
                    //get answers
                    $result = Question::model()->getQuestionsForStatistics('title as code, question as answer', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1]['key1'] = $result;
                    break;



                case "Q": // Multiple Short Text

                    //get subqestions
                    $result = Question::model()->getQuestionsForStatistics('title as code, question as answer', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;

                    //----------------------- ARRAYS --------------------------

                case "A": // ARRAY OF 5 POINT CHOICE QUESTIONS

                    //get answers
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;



                    //just like above only a different loop
                case "B": // ARRAY OF 10 POINT CHOICE QUESTIONS
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;



                case "C": // ARRAY OF YES\No\gT("Uncertain") QUESTIONS
                    //get answers
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;



                    //similiar to the above one
                case "E": // ARRAY OF Increase/Same/Decrease QUESTIONS
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;

                case ";":  //ARRAY (Multi Flex) (Text)
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}' AND scale_id = 0", 'question_order');
                    $aData['result'][$key1] = $result;
                    foreach($result as $key => $row)
                    {
                        $fresult = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}' AND scale_id = 1", 'question_order');
                        $aData['fresults'][$key1][$key] = $fresult;
                    }
                    break;

                case ":":  //ARRAY (Multi Flex) (Numbers)
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}' AND scale_id = 0", 'question_order');
                    $aData['result'][$key1] = $result;
                    foreach($result as $row)
                    {
                        $fresult = Question::model()->getQuestionsForStatistics('*', "parent_qid=$flt[0] AND language = '{$language}' AND scale_id = 1", 'question_order, title');
                        $aData['fresults'][$key1] = $fresult;
                    }
                    break;
                    /*
                     * For question type "F" and "H" you can use labels.
                     * The only difference is that the labels are applied to column heading
                     * or rows respectively
                     */
                case "F": // FlEXIBLE ARRAY
                case "H": // ARRAY (By Column)
                    //Get answers. We always use the answer code because the label might be too long elsewise
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;

                    //check all the answers
                    foreach($result as $row)
                    {
                        $fresult = Answer::model()->getQuestionsForStatistics('*', "qid=$flt[0] AND language = '{$language}'", 'sortorder, code');
                        $aData['fresults'][$key1] = $fresult;
                    }

                    //$statisticsoutput .= "\t\t\t\t<td>\n";
                    $counter=0;
                    break;



                case "R": //RANKING
                    //get some answers
                    $result = Answer::model()->getQuestionsForStatistics('code, answer', "qid=$flt[0] AND language = '{$language}'", 'sortorder, answer');
                    $aData['result'][$key1] = $result;
                    break;

                case "1": // MULTI SCALE

                    //get answers
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid=$flt[0] AND language = '{$language}'", 'question_order');
                    $aData['result'][$key1] = $result;
                    //loop through answers
                    foreach($result as $key => $row)
                    {

                        //check if there is a dualscale_headerA/B
                        $dshresult = QuestionAttribute::model()->getQuestionsForStatistics('value', "qid=$flt[0] AND attribute = 'dualscale_headerA'", '');
                        $aData['dshresults'][$key1][$key] = $dshresult;


                        $fresult = Answer::model()->getQuestionsForStatistics('*', "qid=$flt[0] AND language = '{$language}' AND scale_id = 0", 'sortorder, code');

                        $aData['fresults'][$key1][$key] = $fresult;


                        $dshresult2 = QuestionAttribute::model()->getQuestionsForStatistics('value', "qid=$flt[0] AND attribute = 'dualscale_headerB'", '');
                        $aData['dshresults2'][$key1][$key] = $dshresult2;
                    }
                    break;

                case "P":  //P - Multiple choice with comments
                case "M":  //M - Multiple choice

                    //get answers
                    $result = Question::model()->getQuestionsForStatistics('title, question', "parent_qid = $flt[0] AND language = '$language'", 'question_order');
                    $aData['result'][$key1] = $result;
                    break;


                    /*
                     * This question types use the default settings:
                     *     L - List (Radio)
                     O - List With Comment
                     P - Multiple choice with comments
                     ! - List (Dropdown)
                     */
                default:

                    //get answers
                    $result = Answer::model()->getQuestionsForStatistics('code, answer', "qid=$flt[0] AND language = '$language'", 'sortorder, answer');
                    $aData['result'][$key1] = $result;
                    break;

            }    //end switch -> check question types and create filter forms

            $currentgroup=$flt[1];

            $counter++;

            //temporary save the type of the previous question
            //used to adjust linebreaks
            $previousquestiontype = $flt[2];

        }

        // ----------------------------------- END FILTER FORM ---------------------------------------

        //~ Yii::app()->loadHelper('admin/statistics');
        //~ $helper = new statistics_helper();
        /* Lectra */
        Yii::import("extendAdminStatitistics.helpers.statisticsHelper");
        Yii::import("extendAdminStatitistics.helpers.statisticsLectraHelper");
        $helper=new statisticsHelper;
        /* Lectra END */

        $showtextinline=isset($_POST['showtextinline']) ? 1 : 0;
        $aData['showtextinline'] = $showtextinline;

        //Show Summary results
        if (isset($summary) && $summary)
        {
            $usegraph=isset($_POST['usegraph']) ? 1 : 0;
            $aData['usegraph'] = $usegraph;
            $outputType = $_POST['outputtype'];


            switch($outputType){
                case 'html':
                    $statisticsoutput .=  $helper->generate_html_chartjs_statistics($surveyid,$summary,$summary,$usegraph,$outputType,'DD',$statlang);

                    break;
                case 'pdf':
                    $helper->generate_statistics($surveyid,$summary,$summary,$usegraph,$outputType,'I',$statlang);
                    exit;
                    break;
                case 'xls':
                    $helper->generate_statistics($surveyid,$summary,$summary,$usegraph,$outputType,'DD',$statlang);
                    exit;
                    break;
                default:
                    break;
            }

        }    //end if -> show summary results

        $usegraph=isset($_POST['usegraph']) ? 1 : 0;
        $aData['usegraph'] = $usegraph;

        $aData['sStatisticsLanguage']=$statlang;
        $aData['output'] = $statisticsoutput;
        $aData['summary'] = $summary;


        $error = '';
        if (!function_exists("gd_info"))
        {
            $error .= '<br />'.gT('You do not have the GD Library installed. Showing charts requires the GD library to function properly.');
            $error .= '<br />'.gT('visit http://us2.php.net/manual/en/ref.image.php for more information').'<br />';
        }
        else if (!function_exists("imageftbbox")) {
            $error .= '<br />'.gT('You do not have the Freetype Library installed. Showing charts requires the Freetype library to function properly.');
            $error .= '<br />'.gT('visit http://us2.php.net/manual/en/ref.image.php for more information').'<br />';
        }

        $aData['error'] = $error;
        $aData['oStatisticsHelper'] = $helper;
        $aData['fresults'] = (isset($aData['fresults']))?$aData['fresults']:false;
        $aData['dateformatdetails'] = getDateFormatData(Yii::app()->session['dateformat']);

        $this->_renderWrappedTemplate('export', 'statistics_view', $aData);
        Yii::app()->end();
    }

    /**
     * Render satistics for users
     */
     public function simpleStatistics($surveyid)
     {
         $usegraph=1;
         $iSurveyId =  sanitize_int($surveyid);
         $aData['surveyid'] = $iSurveyId;
         $showcombinedresults = 0;
         $maxchars = 50;
         $statisticsoutput ='';
         $cr_statisticsoutput = '';

         // Set language for questions and answers to base language of this survey
         $language = Survey::model()->findByPk($surveyid)->language;
         $summary = array();
         $summary[0] = "datestampE";
         $summary[1] = "datestampG";
         $summary[2] = "datestampL";
         $summary[3] = "idG";
         $summary[4] = "idL";

         // 1: Get list of questions from survey
         $rows = Question::model()->getQuestionList($surveyid, $language);

         //SORT IN NATURAL ORDER!
         usort($rows, 'groupOrderThenQuestionOrder');

        // The questions to display (all question)
        foreach($rows as $row)
        {
            $type=$row['type'];
            if( $type=="T" ||  $type=="N")
            {
                $summary[] = $type.$iSurveyId.'X'.$row['gid'].'X'.$row['qid'];
            }
            switch ( $type )
            {

                // Double scale cases
                case ":":
                    $qidattributes=getQuestionAttributeValues($row['qid']);
                    if(!$qidattributes['input_boxes'])
                    {
                        $qid = $row['qid'];
                        $results = Question::model()->getQuestionsForStatistics('*', "parent_qid='$qid' AND language = '{$language}' AND scale_id = 0", 'question_order, title');
                        $fresults = Question::model()->getQuestionsForStatistics('*', "parent_qid='$qid' AND language = '{$language}' AND scale_id = 1", 'question_order, title');
                        foreach($results as $row1)
                        {
                            foreach($fresults as $row2)
                            {
                                $summary[] = $iSurveyId.'X'.$row['gid'].'X'.$row['qid'].$row1['title'].'_'.$row2['title'];
                            }
                        }
                    }
                break;

                case "1":
                    $qid = $row['qid'];
                    $results = Question::model()->getQuestionsForStatistics('*', "parent_qid='$qid' AND language = '{$language}'", 'question_order, title');
                    foreach($results as $row1)
                    {
                        $summary[] = $iSurveyId.'X'.$row['gid'].'X'.$row['qid'].$row1['title'].'#0';
                        $summary[] = $iSurveyId.'X'.$row['gid'].'X'.$row['qid'].$row1['title'].'#1';
                    }

                break;

                case "R": //RANKING
                    $qid = $row['qid'];
                    $results = Question::model()->getQuestionsForStatistics('title, question', "parent_qid='$qid' AND language = '{$language}'", 'question_order');
                    $count = count($results);
                    //loop through all answers. if there are 3 items to rate there will be 3 statistics
                    for ($i=1; $i<=$count; $i++)
                    {
                        $summary[] = $type.$iSurveyId.'X'.$row['gid'].'X'.$row['qid'].'-'.$i;
                    }
                break;

                // Cases with subquestions
                case "A":
                case "F": // FlEXIBLE ARRAY
                case "H": // ARRAY (By Column)
                case "E":
                case "B":
                case "C":
                    //loop through all answers. if there are 3 items to rate there will be 3 statistics
                    $qid = $row['qid'];
                    $results = Question::model()->getQuestionsForStatistics('title, question', "parent_qid='$qid' AND language = '{$language}'", 'question_order');
                    foreach($results as $row1)
                    {
                        $summary[] = $iSurveyId.'X'.$row['gid'].'X'.$row['qid'].$row1['title'];
                    }
                break;

                // Cases with subanwsers, need a question type as first letter
                case "P":  //P - Multiple choice with comments
                case "M":  //M - Multiple choice
                case "S":
                    $summary[] = $type.$iSurveyId.'X'.$row['gid'].'X'.$row['qid'];
                break;

                // Not shown (else would only show 'no answer' )
                case "K":
                case "*":
                case "D":
                case "T": // Long free text
                case "U": // Huge free text
                case "|": // File Upload, we don't show it
                case "N":
                case "Q":
                case ';':

                    break;


                default:
                    $summary[] = $iSurveyId.'X'.$row['gid'].'X'.$row['qid'];
                break;
            }
        }


        // ----------------------------------- END FILTER FORM ---------------------------------------

        //~ Yii::app()->loadHelper('admin/statistics');
        //~ $helper = new statistics_helper();
        Yii::import("extendAdminStatitistics.helpers.statisticsHelper");
        Yii::import("extendAdminStatitistics.helpers.statisticsLectraHelper");
        $helper=new statisticsHelper;
        $showtextinline=isset($_POST['showtextinline']) ? 1 : 0;
        $aData['showtextinline'] = $showtextinline;

        //Show Summary results
        $aData['usegraph'] = $usegraph;
        $outputType = 'html';
        $statlang=returnGlobal('statlang');
        $statisticsoutput .=  $helper->generate_simple_statistics($surveyid,$summary,$summary,$usegraph,$outputType,'DD',$statlang);

        $aData['sStatisticsLanguage']=$statlang;
        $aData['output'] = $statisticsoutput;
        $aData['summary'] = $summary;
        $aData['oStatisticsHelper'] = $helper;
        $aData['menu']['expertstats'] =  true;

        //Call the javascript file
        $this->registerScriptFile( 'ADMIN_SCRIPT_PATH', 'statistics.js');
        $this->registerScriptFile( 'ADMIN_SCRIPT_PATH', 'json-js/json2.min.js');
        echo $this->_renderWrappedTemplate('export', 'statistics_user_view', $aData);
    }

    private function _addPseudoParams($params)
    {
        // Return if params isn't an array
        if (empty($params) || !is_array($params))
        {
            return $params;
        }
        $pseudos = array(
        'id' => 'iId',
        'gid' => 'iGroupId',
        'qid' => 'iQuestionId',
        'sid' => array('iSurveyId', 'iSurveyID'),
        'surveyid' => array('iSurveyId', 'iSurveyID'),
        'srid' => 'iSurveyResponseId',
        'scid' => 'iSavedControlId',
        'uid' => 'iUserId',
        'ugid' => 'iUserGroupId',
        'fieldname' => 'sFieldName',
        'fieldtext' => 'sFieldText',
        'action' => 'sAction',
        'lang' => 'sLanguage',
        'browselang' => 'sBrowseLang',
        'tokenids' => 'aTokenIds',
        'tokenid' => 'iTokenId',
        'subaction' => 'sSubAction',
        );

        foreach ($pseudos as $key => $pseudo)
        {
            if (!empty($params[$key]))
            {
                $pseudo = (array) $pseudo;

                foreach ($pseudo as $pseud)
                {
                    if (empty($params[$pseud]))
                    {
                        $params[$pseud] = $params[$key];
                    }
                }
            }
        }
        $params['sa']=empty($params['sa']) ? "run" : $params['sa'];
        return $params;
    }
}
