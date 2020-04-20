<?php

namespace Kanboard\Plugin\Bigboard\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Model\UserMetadataModel;

/**
 * Class BoardAjaxController.
 */
class BoardAjaxController extends BaseController
{
    /**
     * Save new task positions (Ajax request made by the drag and drop).
     */
    public function save()
    {
        $values = $this->request->getJson();

        if (!$values['src_project_id'] || !$this->request->isAjax()) {
            throw new AccessForbiddenException();
        }

        if ($values['dst_project_id'] != $values['src_project_id']) {
            list($valid) = $this->taskValidator->validateProjectModification([
                'id' => intval($values['task_id']),
                'project_id' => intval($values['dst_project_id']),
                'swimlane_id' => intval($values['dst_swimlane_id']),
                'column_id' => intval($values['dst_column_id']),
                'category_id' => intval($values['category_id']),
                'owner_id' => intval($values['owner_id']),
            ]);

            if (!$valid) {
                throw new AccessForbiddenException(e('Malformed Request'));

                return;
            }

            if ($valid && !$this->taskProjectMoveModel->moveToProject($values['task_id'], $values['dst_project_id'], $values['dst_swimlane_id'], $values['dst_column_id'], $values['category_id'], $values['owner_id'])) {
                throw new AccessForbiddenException(e('Project cant be moved'));

                return;
            }

            if (!$this->helper->projectRole->canMoveTask($values['dst_project_id'], $values['dst_column_id'], $values['dst_column_id'])) {
                throw new AccessForbiddenException(e("You don't have the permission to move this task"));

                return;
            }
        } else {
            if (!$this->helper->projectRole->canMoveTask($values['src_project_id'], $values['src_column_id'], $values['dst_column_id'])) {
                throw new AccessForbiddenException(e("You don't have the permission to move this task"));

                return;
            }
        }

        // Could result in a false return, due to the position being the same
        $result = $this->taskPositionModel->movePosition(
            $values['dst_project_id'],
            $values['task_id'],
            $values['dst_column_id'],
            $values['position'],
            $values['dst_swimlane_id']
        );

        $this->response->html($this->renderBoard($values['dst_project_id']), 201);
    }

    /**
     * Check if the board has been changed.
     */
    public function check()
    {
        $project_id = $this->request->getIntegerParam('project_id');
        $timestamp = $this->request->getIntegerParam('timestamp');

        if (!$project_id || !$this->request->isAjax()) {
            throw new AccessForbiddenException();
        }
        if (!$this->projectModel->isModifiedSince($project_id, $timestamp)) {
            $this->response->status(304);
        } else {
            $this->response->html($this->renderBoard($project_id));
        }
    }

    /**
     * Reload the board with new filters.
     */
    public function reload()
    {
        $project_id = $this->request->getIntegerParam('project_id');

        if (!$project_id || !$this->request->isAjax()) {
            throw new AccessForbiddenException();
        }

        $values = $this->request->getJson();
        $this->userSession->setFilters($project_id, empty($values['search']) ? '' : $values['search']);

        $this->response->html($this->renderBoard($project_id));
    }

    /**
     * Enable collapsed mode.
     */
    public function collapse()
    {
        $this->changeDisplayMode(1);
    }

    /**
     * Enable expanded mode.
     */
    public function expand()
    {
        $this->changeDisplayMode(0);
    }

    // record into database status of collapsed or expanded project in bigboard view
    public function collapseProject()
    {
        $projectid = $_GET['project_id'];
        $user = $this->getUser();
        $userid = $user['id'];        
        $collapsed = $this->bigboardModel->collapseFind($projectid, $user['id']);
        if ($collapsed) {
            $status = $this->bigboardModel->collapseDrop($collapsed['id']);
        } else {
            $status = $this->bigboardModel->collapseTake($projectid, $user['id']);
        }
        $this->response->json(['status' => $status]);
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
     * clear all projects from collapsed status to display all of them as expanded.
     */
    public function expandAllProjects()
    {
        $user = $this->getUser();

        return $this->bigboardModel->collapseClear($user['id']);
    }

    /**
     * Render board.
     *
     * @param int $project_id
     *
     * @return string
     */
    protected function renderBoard($project_id)
    {
        return $this->template->render('bigboard:board/table_container', [
            'project' => $this->projectModel->getById($project_id),
            'board_private_refresh_interval' => $this->configModel->get('board_private_refresh_interval'),
            'board_highlight_period' => $this->configModel->get('board_highlight_period'),
            'swimlanes' => $this->taskLexer
                ->build($this->userSession->getFilters($project_id))
                ->format($this->boardFormatter->withProjectId($project_id)),
        ]);
    }

    /**
     * Change display mode.
     *
     * @param int $mode
     */
    private function changeDisplayMode($mode)
    {
        $project_id = $this->request->getIntegerParam('project_id');
        $this->userMetadataCacheDecorator->set(UserMetadataModel::KEY_BOARD_COLLAPSED.$project_id, $mode);

        if ($this->request->isAjax()) {
            $this->response->html($this->renderBoard($project_id));
        } else {
            $this->response->redirect($this->helper->url->to('BoardViewController', 'show', ['project_id' => $project_id]));
        }
    }
}
