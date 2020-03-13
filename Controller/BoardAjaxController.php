<?php

namespace Kanboard\Plugin\Bigboard\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Core\Base;
use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Model\UserMetadataModel;

/**
 * Class BoardAjaxController
 *
 * @package Kanboard\Controller
 * @author  Fredric Guillot
 */
class BoardAjaxController extends BaseController
{
    /**
     * Save new task positions (Ajax request made by the drag and drop)
     *
     * @access public
     */
    public function save()
    {
        $values = $this->request->getJson();
        
        if (! $values['old_project_id'] || ! $this->request->isAjax()) {
            throw new AccessForbiddenException();
        }

        if (! $this->helper->projectRole->canMoveTask($values['old_project_id'], $values['src_column_id'], $values['dst_column_id'])) {
            throw new AccessForbiddenException(e("You don't have the permission to move this task"));
        }
		
		$movedBetweenProjects = false;
        if ($values['project_id'] != $values['old_project_id']){
            list($valid, ) = $this->taskValidator->validateProjectModification(array(
                "id" => intval($values['task_id']),
                "project_id" => intval($values['project_id']),
                "swimlane_id" => intval($values['swimlane_id']),
                "column_id" => intval($values['dst_column_id']),
                "category_id" => intval($values['category_id']),
                "owner_id" => intval($values['owner_id'])
            ));
            
            if(!$valid){
                throw new AccessForbiddenException(e("Malformed Request"));
            }
            
            if (!($valid && $this->taskProjectMoveModel->moveToProject($values['task_id'], $values['project_id'], $values['swimlane_id'], $values['dst_column_id'], $values['category_id'], $values['owner_id']))) {
                throw new AccessForbiddenException(e("Project cant be moved"));
                return;
            }
			
			$movedBetweenProjects = true;
        }
		$result = $this->taskPositionModel->movePosition(
			$values['project_id'],
			$values['task_id'],
			$values['dst_column_id'],
			$values['position'],
			$values['swimlane_id']
		);

		if (! $result) {
			$this->response->status(400);
			return;
		}
		if($movedBetweenProjects){
			$this->response->html($this->renderBoard($values['project_id']), 201);				
		}else{
			$this->response->html($this->renderBoard($values['old_project_id']), 201);				
		}
    }

    /**
     * Check if the board have been changed
     *
     * @access public
     */
    public function check()
    {
        $project_id = $this->request->getIntegerParam('project_id');
        $timestamp = $this->request->getIntegerParam('timestamp');

        if (! $project_id || ! $this->request->isAjax()) {
            throw new AccessForbiddenException();
        } elseif (! $this->projectModel->isModifiedSince($project_id, $timestamp)) {
            $this->response->status(304);
        } else {
            $this->response->html($this->renderBoard($project_id));
        }
    }

    /**
     * Reload the board with new filters
     *
     * @access public
     */
    public function reload()
    {
        $project_id = $this->request->getIntegerParam('project_id');

        if (! $project_id || ! $this->request->isAjax()) {
            throw new AccessForbiddenException();
        }

        $values = $this->request->getJson();
        $this->userSession->setFilters($project_id, empty($values['search']) ? '' : $values['search']);

        $this->response->html($this->renderBoard($project_id));
    }

    /**
     * Enable collapsed mode
     *
     * @access public
     */
    public function collapse()
    {
        $this->changeDisplayMode(1);
    }

    /**
     * Enable expanded mode
     *
     * @access public
     */
    public function expand()
    {
        $this->changeDisplayMode(0);
    }

    /**
     * Change display mode
     *
     * @access private
     * @param  int $mode
     */
    private function changeDisplayMode($mode)
    {
        $project_id = $this->request->getIntegerParam('project_id');
        $this->userMetadataCacheDecorator->set(UserMetadataModel::KEY_BOARD_COLLAPSED.$project_id, $mode);

        if ($this->request->isAjax()) {
            $this->response->html($this->renderBoard($project_id));
        } else {
            $this->response->redirect($this->helper->url->to('BoardViewController', 'show', array('project_id' => $project_id)));
        }
    }

    /**
     * Render board
     *
     * @access protected
     * @param  integer $project_id
     * @return string
     */
    protected function renderBoard($project_id)
    {
        return $this->template->render('bigboard:board/table_container', array(
            'project' => $this->projectModel->getById($project_id),
            'board_private_refresh_interval' => $this->configModel->get('board_private_refresh_interval'),
            'board_highlight_period' => $this->configModel->get('board_highlight_period'),
            'swimlanes' => $this->taskLexer
                ->build($this->userSession->getFilters($project_id))
                ->format($this->boardFormatter->withProjectId($project_id))
        ));
    }
        
                 
         /*
         * AJAX called : record into database status of selected or unselected project
         * deprecated : for reference only (replaced by form validation method savelist()
         */
        public function selectProject()
    {
        $project = $this->getProject();
        $user = $this->getUser();
        $selected = $this->bigboardModel->selectFind($project['id'], $user['id']);
        if ($selected) {
            $status = $this->bigboardModel->selectDrop($selected['id']);
        } else {
            $status = $this->bigboardModel->selectTake($project['id'], $user['id']);
        }

        $this->response->json(array('status' => $status));
    }

         /*
         * record into database status of collapsed or expanded project in bigboard view
         */
        public function collapseProject()
    {
        $projectid = $_GET["project_id"];
        $user = $this->getUser();
                $userid = $user['id'];
                error_log("WIP collapseProject() project $projectid for user $userid would be added to the table");
                $collapsed = $this->bigboardModel->collapseFind($projectid, $user['id']);
        if ($collapsed) {
            $status = $this->bigboardModel->collapseDrop($collapsed['id']);
        } else {
            $status = $this->bigboardModel->collapseTake($projectid, $user['id']);
        }
        $this->response->json(array('status' => $status));
    }        
        
         /**
      * get all selected projects from bigboard view to store them as collapsed.
      */
         public function collapseAllProjects()
        {
                $user = $this->getUser();
                $projects_id = $this->bigboardModel->selectFindAllProjectsById($user['id']);
                $this->bigboardModel->collapseClear($user['id']);
                foreach ($projects_id as $project_id) {
                        $this->bigboardModel->collapseTake($project_id, $user['id']);
                }
                return true;
        }
         /**
      * clear all projects from collapsed status to display all of them as expanded
      */
         public function expandAllProjects()
        {
                $user = $this->getUser();
                return $this->bigboardModel->collapseClear($user['id']);
        }        
}
