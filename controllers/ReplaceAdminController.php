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
class ReplaceAdminController extends AdminController
{
    /**
    * Initialises this controller, does some basic checks and setups
    *
    * @access protected
    * @return void
    */
    protected function _init()
    {
        parent::_init();
    }
    /**
    * Routes all the actions to their respective places : here replace statistics
    *
    * @access public
    * @return array
    */
    public function actions()
    {
        $aActions = parent::actions();
        $aActions['adminStatisticsController']="extendAdminStatitistics.controllers.adminStatisticsController";
        return $aActions;
    }
    //public function run(
}
