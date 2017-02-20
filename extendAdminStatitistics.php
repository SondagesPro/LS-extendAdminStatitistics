<?php
/**
 * extend admin statitics with own controller extending the LS core controller
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2016 Denis Chenu <http://www.sondages.pro>
 * @license GPL v3
 * @version 0.1.1
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
class extendAdminStatitistics extends \ls\pluginmanager\PluginBase
{

  protected $storage = 'DbStorage';

  /**
   * Did it's already own controller
   */
  private $ownController=false;
  static protected $name = 'extendAdminStatitistics';
  static protected $description = 'extend admin statitics with own controller extending the LS core controller';

  public function init()
  {
    $this->subscribe('beforeControllerAction', 'setAdminStatitistics');
  }
  public function setAdminStatitistics()
  {
    if(!$this->ownController && $this->event->get('controller')=='admin' && $this->event->get('action')=='statistics')
    {
      Yii::setPathOfAlias('extendAdminStatitistics', dirname(__FILE__));
      Yii::import("extendAdminStatitistics.controllers.ReplaceAdminController");
      $this->ownController=true;
      $oAdminController= new ReplaceAdminController('admin');
      $oAdminController->run("adminStatisticsController");// to try to deactivate
      $this->event->set('run',false);
    }
  }

    public function beforeToolsMenuRender()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');

        $href = Yii::app()->createUrl(
            'admin/pluginhelper',
            array(
                'sa' => 'sidebody',
                'plugin' => 'extendAdminStatitistics',
                'method' => 'actionIndex',
                'surveyId' => $surveyId
            )
        );

        $menuItem = new MenuItem(array(
            'label' => gT('Statistics'),
            'iconClass' => 'fa fa-table',
            'href' => $href
        ));

        $event->append('menuItems', array($menuItem));
    }
    /**
     * @return string
     */
    public function actionIndex($surveyId)
    {
        Yii::setPathOfAlias('extendAdminStatitistics', dirname(__FILE__));
        //$oAdminController
    }
}
