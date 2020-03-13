Kanboard.BoardDragAndDrop = function(app) {
    this.app = app;
    this.savingInProgress = false;
};

Kanboard.BoardDragAndDrop.prototype.execute = function() {
    if (this.app.hasId("board")) {
        this.executeListeners();
        this.dragAndDrop();
    }
};

Kanboard.BoardDragAndDrop.prototype.dragAndDrop = function() {
    var self = this;
    var dropzone = $(".board-task-list");

    // Run for every Board List, connecting the Items within the same project id
    dropzone.each(function() {
        // Set dropzone height to the height of the table cell
        $(this).css("min-height", $(this).parent().height());

        var project_id = $(this).closest("table[id=board]").attr("data-project-id");

        var params = {
            forcePlaceholderSize: true,
            tolerance: "pointer",
            connectWith: ".sortable-column",
            placeholder: "draggable-placeholder",
            items: ".draggable-item",
            stop: function(event, ui) {
                var task = ui.item;
                var taskId = task.attr('data-task-id');
                var taskPosition = task.attr('data-position');
                var taskColumnId = task.attr('data-column-id');
                var taskNewSwimlaneId = task.attr('data-swimlane-id');
                var taskCategoryId = task.attr('data-category-id');
                var taskOwnerId = task.attr('data-owner-id');

                var taskProjectId = task.attr('data-project-id');

                var newColumnId = task.parent().attr("data-column-id");
                var newSwimlaneId = task.parent().attr('data-swimlane-id');
                var newProjectId = task.parent().attr('data-project-id');

                var newPosition = task.index() + 1;

                var boardId = task.closest("table").attr("data-project-id");
                var saveURL = task.closest("table").attr("data-save-url");

                task.removeClass("draggable-item-selected");

                if (newColumnId != taskColumnId || newSwimlaneId != taskSwimlaneId || newPosition != taskPosition || newProjectId != taskProjectId ) {
                    self.changeTaskState(taskId);
                    self.save(saveURL, boardId, taskId, taskColumnId, newColumnId, newPosition, newSwimlaneId, taskProjectId, newProjectId, taskCategoryId, taskOwnerId );                        
                }
            },
            start: function(event, ui) {
                ui.item.addClass("draggable-item-selected");
                ui.placeholder.height(ui.item.height());
            }
        };

        if (isMobile.any) {
            $(".task-board-sort-handle").css("display", "inline");
            params.handle = ".task-board-sort-handle";
        }
        
        $(this).sortable(params);
    });
};

Kanboard.BoardDragAndDrop.prototype.changeTaskState = function(taskId) {
    var task = $("div[data-task-id=" + taskId + "]");
    task.addClass('task-board-saving-state');
    task.find('.task-board-saving-icon').show();
};

Kanboard.BoardDragAndDrop.prototype.save = function(saveURL, boardId, taskId, srcColumnId, dstColumnId, position, swimlaneId, oldProjectId, newProjectId, taskCategoryId, taskOwnerId ) {
    var self = this;
    self.app.showLoadingIcon();
    self.savingInProgress = true;

    $.ajax({
        cache: false,
        url: saveURL,
        contentType: "application/json",
        type: "POST",
        processData: false,
        data: JSON.stringify({
            "task_id": taskId,
            "src_column_id": srcColumnId,
            "dst_column_id": dstColumnId,
            "swimlane_id": swimlaneId,
            "project_id": newProjectId,
            "old_project_id": oldProjectId,
            "category_id": taskCategoryId,
            "owner_id": taskOwnerId,
            "position": position
        }),
        success: function(data) {
            self.refresh(boardId,data);
            self.savingInProgress = false;
        },
        error: function() {
            self.app.hideLoadingIcon();
            self.savingInProgress = false;
        },
        statusCode: {
            403: function(data) {
                window.alert(data.responseJSON.message);
                document.location.reload(true);
            }
        }
    });
};

Kanboard.BoardDragAndDrop.prototype.refresh = function(boardId, data) {

    $("div[id=board-container][data-project-id=" + boardId + "]").replaceWith(data);

    this.app.hideLoadingIcon();
    this.executeListeners();
    this.dragAndDrop();
};

Kanboard.BoardDragAndDrop.prototype.executeListeners = function() {
    for (var className in this.app.controllers) {
        var controller = this.app.get(className);

        if (typeof controller.onBoardRendered === "function") {
            controller.onBoardRendered();
        }
    }
};
